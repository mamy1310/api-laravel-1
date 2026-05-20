<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserBase',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Mamy Rakoto'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'mamy.rakoto@gmail.com'),
    ]
)]
#[OA\Schema(
    schema: 'LoginInput',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'mamy.rakoto@gmail.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'secret1234'),
    ]
)]
#[OA\Schema(
    schema: 'RegisterInput',
    required: ['password'],
    allOf: [
        new OA\Schema(ref: '#/components/schemas/UserBase'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'secret1234'),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'User',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/UserBase'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
            ]
        ),
    ]
)]
class UserController extends Controller
{
    #[OA\Post(
        path: '/register',
        summary: 'Inscription d\'un nouvel utilisateur',
        description: 'Crée un compte utilisateur avec hachage du mot de passe.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return new UserResource($user);
    }

    #[OA\Post(
        path: '/login',
        summary: 'Authentification utilisateur',
        description: 'Vérifie email/mot de passe et retourne un token Sanctum. Throttle: 10 req/min.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authentification réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456...'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                    ]
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::query()->where('email', '=', $data['email'], 'and')->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(
                ['message' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Déconnexion (révoque le token courant)',
        description: 'Supprime le token Sanctum utilisé pour la requête.',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Déconnexion réussie (no content)'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
        ]
    )]
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) { // To avoid an IDE intelephense error
            $token->delete();
        }

        return response()->noContent();
    }
}
