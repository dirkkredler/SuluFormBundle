<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\FormBundle\Controller;

use Sulu\Bundle\FormBundle\Dynamic\FormFieldTypeInterface;
use Sulu\Bundle\FormBundle\Mail\HelperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;

/**
 * Generated by https://github.com/alexander-schranz/sulu-backend-bundle.
 */
class TemplateController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function formAction(Request $request)
    {
        $widths = [];
        foreach ($this->getParameter('sulu_form.dynamic_widths') as $id => $name) {
            $widths[] = [
                'id' => $id,
                'name' => $name,
            ];
        }

        $types = $this->get('sulu_form.dynamic.form_field_type_pool')->all();
        $receiverTypes = [
            'to' => HelperInterface::MAIL_RECEIVER_TO,
            'cc' => HelperInterface::MAIL_RECEIVER_CC,
            'bcc' => HelperInterface::MAIL_RECEIVER_BCC,
        ];

        return $this->render(
            'SuluFormBundle:forms:template.html.twig',
            [
                'types' => $this->getSortedTypes($types),
                'widths' => $widths,
                'receiverTypes' => $receiverTypes,
                'locale' => $request->get('locale', $request->getLocale()),
                'autoTitle' => $this->getParameter('sulu_form.dynamic_auto_title'),
                'fallbackEmails' => [
                    'from' => $this->getParameter('sulu_form.mail.from'),
                    'to' => $this->getParameter('sulu_form.mail.to'),
                ],
            ]
        );
    }

    /**
     * @param FormFieldTypeInterface[] $types
     *
     * @return FormFieldTypeInterface[]
     */
    private function getSortedTypes($types = [])
    {
        /** @var Translator $translator */
        $translator = $this->get('translator');
        $locale = $this->getUser()->getLocale();

        $sortedTypes = [];
        $returnTypes = [];

        $i = 0;
        foreach ($types as $alias => $type) {
            $translation = $translator->trans($type->getConfiguration()->getTitle(), [], 'backend', $locale);
            $group = $type->getConfiguration()->getGroup();
            $sortedTypes[$group . '_' . $translation . '_' . $i] = [
                'alias' => $alias,
                'type' => $type,
            ];
            ++$i;
        }

        ksort($sortedTypes);
        foreach ($sortedTypes as $sortedType) {
            $returnTypes[$sortedType['alias']] = $sortedType['type'];
        }

        return $returnTypes;
    }
}