<?php

namespace Softspring\CmsTranslationPlugin\Controller;

use Softspring\CmsTranslationPlugin\Api\Driver\TranslatorDriverInterface;
use Softspring\CmsTranslationPlugin\Form\ApiTranslateForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslatorController extends AbstractController
{
    public function __construct(protected TranslatorDriverInterface $translatorApi)
    {
    }

    public function translate(Request $request): JsonResponse
    {
        $form = $this->createForm(ApiTranslateForm::class)->handleRequest($request);

        if (!$form->isSubmitted()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Form is not submitted',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$form->isValid()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Form is not valid',
            ], Response::HTTP_BAD_REQUEST);
        }

        $originText = $form->getData()['text'];
        $targetLanguage = $form->getData()['target'];
        $sourceLanguage = $form->getData()['source'];

        $result = $this->translatorApi->translate($originText, $targetLanguage, $sourceLanguage, ['format' => 'html']);

        return new JsonResponse($result->toArray());
    }
}
