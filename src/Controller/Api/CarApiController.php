<?php

namespace App\Controller\Api;

use App\Repository\ModelRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/car', name: 'api_car_')]
class CarApiController extends AbstractController
{
    #[Route('/models/{brandId}', name: 'get_models', methods: ['GET'], requirements: ['brandId' => '\d+'])]
    public function getModelsByBrand(
        string $brandId,
        ModelRepository $modelRepository
    ): Response {
        $models = $modelRepository->findByBrand((int)$brandId);
        
        $html = '<select id="model-select" name="car[model]" class="form-select" required>';
        $html .= '<option value="">Sélectionnez un modèle</option>';
        
        foreach ($models as $model) {
            $html .= sprintf(
                '<option value="%d">%s</option>',
                $model->getId(),
                htmlspecialchars($model->getName(), ENT_QUOTES, 'UTF-8')
            );
        }
        
        $html .= '</select>';

        return new Response($html);
    }
}
