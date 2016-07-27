<?php

namespace L91\Sulu\Bundle\FormBundle\Form\Type;

use L91\Sulu\Bundle\FormBundle\Entity\Dynamic;
use L91\Sulu\Bundle\FormBundle\Entity\Form;
use L91\Sulu\Bundle\FormBundle\Entity\FormFieldTranslation;
use L91\Sulu\Bundle\FormBundle\Entity\FormTranslation;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DynamicFormType extends AbstractType
{
    /**
     * @var Form
     */
    private $formEntity;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $structureView;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $systemCollectionId;

    /**
     * DynamicFormType constructor.
     *
     * @param Form $formEntity
     * @param string $locale
     * @param string $name
     * @param string $structureView
     * @param int $systemCollectionId
     */
    public function __construct($formEntity, $locale, $name, $structureView, $systemCollectionId)
    {
        $this->formEntity = $formEntity;
        $this->locale = $locale;
        $this->name = $name;
        $this->structureView = $structureView;
        $this->systemCollectionId = $systemCollectionId;
    }

    /**
     * {@inheritdoc}
     */
    protected $dataClass = Dynamic::class;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->formEntity->getFields() as $field) {
            $translation = $field->getTranslation($this->locale);
            $name = $field->getKey();
            $type = TextType::class;
            $options = ['constraints' => [], 'attr' => [], 'required' => false];

            // skip $type headline, use for the next field
            if ('headline' === $field->getType()) {
                $headline = $translation->getTitle();
                continue;
            }

            // headline
            if (isset($headline) && '' !== $headline) {
                $options['attr']['headline'] = $headline;
                $headline = '';
            }

            // title
            $title = '';
            $placeholder = '';
            $width = 'full';

            // title / placeholder
            if ($translation) {
                $title = $translation->getTitle();
                $placeholder = $translation->getPlaceholder();
            }

            // width
            if ($field->getWidth()) {
                $width = $field->getWidth();
            }

            $options['label'] = $title;
            $options['required'] = $field->getRequired();
            $options['attr']['width'] = $width;
            $options['attr']['placeholder'] = $placeholder;

            // required
            if ($field->getRequired()) {
                $options['constraints'][] = new NotBlank();
            }

            // Form Type
            switch ($field->getType()) {
                case 'spacer':
                    $type = HiddenType::class;
                    $options['attr']['spacer'] = true;
                    break;
                case 'free_text':
                    $type = HiddenType::class;
                    $options['attr']['free_text'] = true;
                    break;
                case 'salutation':
                    $type = ChoiceType::class;

                    $options['choices'] = [
                        'mr' => 'l91_sulu_form.salutation_mr',
                        'ms' => 'l91_sulu_form.salutation_ms',
                    ];
                    break;
                case 'headline':
                    // headline is handled separately and used as attribute
                    continue;
                    break;
                case 'textarea':
                    $type = TextareaType::class;
                    break;
                case 'country':
                    $type = CountryType::class;
                    break;
                case 'email':
                    $type = EmailType::class;
                    break;
                case 'date':
                    $type = DateType::class;
                    $options['widget'] = 'single_text';
                    break;
                case 'attachment':
                    $type = FileType::class;
                    break;
                case 'checkbox':
                    $type = CheckboxType::class;
                    break;
                case 'checkboxes':
                    $type = $this->createChoiceType($translation, $options, true, true);
                    break;
                case 'select':
                    $type = $this->createChoiceType($translation, $options);
                    break;
                case 'multiple_select':
                    $type = $this->createChoiceType($translation, $options, false, true);
                    break;
                case 'radio_buttons':
                    $type = $this->createChoiceType($translation, $options, true);
                    $options['attr']['class'] = 'radio-buttons';
                    break;
            }

            $builder->add($name, $type, $options);
        }

        $builder->add('submit', SubmitType::class);
    }

    /**
     * @description Choice Type handles four form types (select, multiple select, radio, checkboxes)
     * (http://symfony.com/doc/current/reference/forms/types/choice.html)
     *
     * @param FormFieldTranslation $translation
     * @param array $options
     * @param bool $expanded
     * @param bool $multiple
     */
    public function createChoiceType($translation, &$options, $expanded = false, $multiple = false)
    {
        if ($translation) {
            // placeholder
            $options['placeholder'] = $translation->getPlaceholder();

            // choices
            $choices = explode("\n", $translation->getOption('choices'));
            $options['choices'] = array_combine($choices, $choices);

            // type
            $options['expanded'] = $expanded;
            $options['multiple'] = $multiple;
        }

        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'dynamic_' . $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerSubject($formData = [])
    {
        return $this->getTranslation()->getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifySubject($formData = [])
    {
        return $this->getTranslation()->getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerFromMailAddress($formData = [])
    {
        $fromMail = $this->getTranslation()->getFromEmail();
        $fromName = $this->getTranslation()->getFromName();

        if (!$fromMail || !$fromName) {
            return;
        }

        return [$fromMail => $fromName];
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifyFromMailAddress($formData = [])
    {
        $fromMail = $this->getTranslation()->getFromEmail();
        $fromName = $this->getTranslation()->getFromName();

        if (!$fromMail || !$fromName) {
            return;
        }

        return [$fromMail => $fromName];
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifyToMailAddress($formData = [])
    {
        $toMail = $this->getTranslation()->getToEmail();
        $toName = $this->getTranslation()->getToName();

        if (!$toMail || !$toName) {
            return;
        }

        return [$toMail => $toName];
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerMail($formData = [])
    {
        return $this->structureView . '-mail/' . $this->name . '-success.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifyMail($formData = [])
    {
        return $this->structureView . '-mail/' . $this->name . '-notify.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifySendAttachments($formData = [])
    {
        return $this->getTranslation()->getSendAttachments();
    }

    /**
     * {@inheritdoc}
     */
    public function getCollectionId()
    {
        return $this->systemCollectionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileFields()
    {
        $fileFields = [];

        foreach ($this->formEntity->getFields() as $field) {
            if ('attachment' === $field->getType()) {
                $fileFields[] = $field->getKey();
            }
        }

        return $fileFields;
    }

    /**
     * @return FormTranslation|null
     */
    public function getTranslation()
    {
        return $this->formEntity->getTranslation($this->locale, true);
    }
}
