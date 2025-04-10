<?php

namespace Company\TypeBundle\Form\Type;

use Company\TypeBundle\Form\DataClass\DateIntervalModel;
use Company\TypeBundle\Form\Transformer\DateIntervalTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateIntervalType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'buttonText' => 'Beállítás',
            'placeholder' => 'Intervallum kiválasztása..',
            'title' => 'Showing data for',
            'model_timezone' => DateIntervalModel::DEFAULT_TIMEZONE,
            'view_timezone' => DateIntervalModel::DEFAULT_TIMEZONE,
            'predefinedDateOffset' => 0,
            'autoSubmit' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $types = array_combine(DateIntervalModel::getTypes(), DateIntervalModel::getTypes());
        $predefinedValues = self::getPredefinedDisplayValuesByDate($options['predefinedDateOffset'], $options['view_timezone']);

        if (!$options['required']) {
            $types = array_merge(['' => $options['placeholder']], $types);
        }

        $builder
            ->add('type', ChoiceType::class, [
                'label' => false,
                'required' => $options['required'],
                'choices' => array_flip($types),
                'attr' => [
                    'class' => 'hidden',
                    'data-predefined-values' => json_encode($predefinedValues),
                ],
            ])
            ->add('dateFrom', DateTimeType::class, [
                'label' => false,
                'required' => $options['required'],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                'model_timezone' => DateIntervalModel::DEFAULT_TIMEZONE,
                'view_timezone' => $options['view_timezone'],
                'attr' => [
                    'class' => 'hidden',
                ],
            ])
            ->add('dateTo', DateTimeType::class, [
                'label' => false,
                'required' => $options['required'],
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'html5' => false,
                'model_timezone' => DateIntervalModel::DEFAULT_TIMEZONE,
                'view_timezone' => $options['view_timezone'],
                'attr' => [
                    'class' => 'hidden',
                ],
            ]);

        $builder->addViewTransformer(new DateIntervalTransformer($options['predefinedDateOffset'], $options['view_timezone']));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (empty($view->vars['value'])) {
            if ($options['required']) {
                $values = DateIntervalModel::createPredefinedValues($options['predefinedDateOffset'], $options['view_timezone']);
                reset($values);
                $dateInterval = current($values);

                $view->vars['value'] = [
                    'type' => key($values),
                    'dateFrom' => $dateInterval->getDateFrom(),
                    'dateTo' => $dateInterval->getDateTo(),
                ];
            } else {
                $view->vars['value'] = [
                    'type' => null,
                    'dateFrom' => null,
                    'dateTo' => null,
                ];
            }
        }

        $value = $view->vars['value'];

        $view->vars['value'] = [
            'type' => $value['type'],
            'dateFrom' => self::convertDateIntoTimezone($value['dateFrom'], $options['view_timezone']),
            'dateTo' => self::convertDateIntoTimezone($value['dateTo'], $options['view_timezone']),
        ];

        $view->vars['buttonText'] = $options['buttonText'];
        $view->vars['predefinedChoiceLabels'] = self::getPredefinedChoiceLabels($options['view_timezone']);
        $view->vars['placeholder'] = $options['placeholder'];
        $view->vars['title'] = $options['title'];
        $view->vars['predefinedCustomKey'] = DateIntervalModel::TYPE_CUSTOM;
        $view->vars['autoSubmit'] = $options['autoSubmit'];
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'company_date_interval';
    }

    private static function getPredefinedDisplayValuesByDate(int $predefinedDateOffset, string $timezone): array
    {
        $values = [];

        foreach (DateIntervalModel::createPredefinedValues($predefinedDateOffset, $timezone) as $key => $interval) {
            $values[$key] = [
                'dateFrom' => self::convertDateIntoTimezone($interval->getDateFrom(), $timezone)->format('Y-m-d'),
                'dateTo' => self::convertDateIntoTimezone($interval->getDateTo(), $timezone)->format('Y-m-d'),
            ];
        }

        return $values;
    }

    private static function getPredefinedChoiceLabels(string $timeZone): array
    {
        $thisYear = new \DateTime('now', new \DateTimeZone($timeZone));
        $lastYear = clone $thisYear;
        $lastYear->sub(new \DateInterval('P1Y'));

        return [
            DateIntervalModel::TYPE_PAST_7_DAYS => 'Elmúlt 7 nap',
            DateIntervalModel::TYPE_PAST_30_DAYS => 'Elmúlt 30 nap',
            DateIntervalModel::TYPE_PAST_60_DAYS => 'Elmúlt 60 nap',
            DateIntervalModel::TYPE_PAST_90_DAYS => 'Elmúlt 90 nap',
            DateIntervalModel::TYPE_THIS_YEAR => $thisYear->format('Y'),
            DateIntervalModel::TYPE_LAST_YEAR => $lastYear->format('Y'),
        ];
    }

    private static function convertDateIntoTimezone(?\DateTime $date, string $timeZone): ?\DateTime
    {
        if (null === $date) {
            return null;
        }

        $tzObj = new \DateTimeZone($timeZone);
        $date = clone $date;
        $date->setTimezone($tzObj);

        return new \DateTime($date->format('Y-m-d H:i:s'));
    }
}
