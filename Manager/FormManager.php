<?php

namespace L91\Sulu\Bundle\FormBundle\Manager;

use L91\Sulu\Bundle\FormBundle\Entity\Form;
use L91\Sulu\Bundle\FormBundle\Entity\FormField;
use L91\Sulu\Bundle\FormBundle\Repository\FormRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generated by https://github.com/alexander-schranz/sulu-backend-bundle.
 */
class FormManager
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var FormRepository
     */
    protected $repository;

    /**
     * EventManager constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param FormRepository $formRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        FormRepository $formRepository
    ) {
        $this->entityManager = $entityManager;
        $this->formRepository = $formRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id, $locale = null)
    {
        return $this->formRepository->findById($id, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($locale = null, $filters)
    {
        return $this->formRepository->findAll($locale, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function count($locale = null, $filters)
    {
        return $this->formRepository->count($locale, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $locale = null, $id = null)
    {
        $form = new Form();

        // find exist or create new entity
        if ($id) {
            $form = $this->findById($id, $locale);
        }

        // Translation
        $translation = $form->getTranslation($locale, true);
        $translation->setTitle(self::getValue($data, 'title'));

        if (!$translation->getId()) {
            $translation->setForm($form);
            $form->addTranslation($translation);
        }

        if (!$form->getId()) {
            $form->setDefaultTranslation($translation);
        }

        foreach (self::getValue($data, 'fields', []) as $field) {
            $field = $form->getField(self::getValue($field, 'key', uniqid('', true)));
            $field->setType(self::getValue($field, 'type'));
            $field->setWidth(self::getValue($field, 'width'));
            $field->setRequired(self::getValue($field, 'required', false));

            $fieldTranslation = $field->getTranslation($locale);
            $fieldTranslation->setTitle(self::getValue($field, 'title'));
            $fieldTranslation->setPlaceholder(self::getValue($field, 'placeholder'));
            $fieldTranslation->setDefaultValue(self::getValue($field, 'defaultValue'));

            if (!$fieldTranslation->getId()) {
                $fieldTranslation->setField($field);
                $field->addTranslation($fieldTranslation);
            }

            if (!$field->getId()) {
                $field->setDefaultTranslation($fieldTranslation);
                $field->setForm($form);
                $form->addField($field);
            }
        }

        // save
        $this->entityManager->persist($form);
        $this->entityManager->flush();

        if (!$id) {
            // to avoid lazy load of sub entities in the serializer reload whole object with sub entities from db
            // remove this when you don`t join anything in `findById`
            $form = $this->findById($form->getId(), $locale);
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id, $locale = null)
    {
        $object = $this->findById($id, $locale);

        if (!$object) {
            return null;
        }

        $this->entityManager->remove($object);
        $this->entityManager->flush();

        return $object;
    }

    /**
     * @param $data
     * @param $value
     * @param null $default
     * @param string $type
     *
     * @return mixed
     */
    protected static function getValue($data, $value, $default = null, $type = null)
    {
        if (isset($data[$value])) {
            if ($type === 'date') {
                if (!$data[$value]) {
                    return $default;
                }

                return new \DateTime($data[$value]);
            }

            return $data[$value];
        }

        return $default;
    }
}
