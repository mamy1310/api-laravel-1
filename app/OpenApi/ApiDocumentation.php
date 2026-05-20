<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Books API',
    description: 'API RESTful de gestion de livres + authentification.',
)]
#[OA\Server(
    url: 'http://localhost:8000/api/v1',
    description: 'Local dev server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'sanctum-token'
)]
#[OA\Tag(name: 'Auth', description: 'Inscription, connexion, déconnexion')]
#[OA\Tag(name: 'Books', description: 'Gestion des livres (CRUD)')]
#[OA\Schema(
    schema: 'ErrorMessage',
    properties: [
        new OA\Property(property: 'message', type: 'string'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(property: 'errors', type: 'object', additionalProperties: new OA\AdditionalProperties(
            type: 'array',
            items: new OA\Items(type: 'string')
        )),
    ]
)]
#[OA\Response(
    response: 'Unauthenticated',
    description: 'Non authentifié, token manquant ou invalide',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorMessage',
        example: ['message' => 'Unauthenticated.']
    )
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Ressource introuvable',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorMessage',
        example: ['message' => 'No query results for model.']
    )
)]
#[OA\Response(
    response: 'ValidationFailed',
    description: 'Validation échouée',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
)]
final class ApiDocumentation
{
    //
}
