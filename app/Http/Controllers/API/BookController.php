<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Book',
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Dune'),
        new OA\Property(property: 'author', type: 'string', example: 'FRANK HERBERT'),
        new OA\Property(property: 'summary', type: 'string', example: 'Épopée science-fiction sur Arrakis.'),
        new OA\Property(property: 'isbn', type: 'string', example: '9780441013593'),
        new OA\Property(property: '_links', type: 'object', properties: [
            new OA\Property(property: 'self', type: 'string', format: 'uri'),
            new OA\Property(property: 'update', type: 'string', format: 'uri'),
            new OA\Property(property: 'delete', type: 'string', format: 'uri'),
            new OA\Property(property: 'all', type: 'string', format: 'uri'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'BookInput',
    required: ['title', 'author', 'summary', 'isbn'],
    properties: [
        new OA\Property(property: 'title', type: 'string', minLength: 3, maxLength: 255, example: 'Dune'),
        new OA\Property(property: 'author', type: 'string', minLength: 3, maxLength: 100, example: 'Frank Herbert'),
        new OA\Property(property: 'summary', type: 'string', minLength: 10, maxLength: 500, example: 'Épopée science-fiction sur Arrakis.'),
        new OA\Property(property: 'isbn', type: 'string', minLength: 13, maxLength: 13, example: '9780441013593'),
    ]
)]
class BookController extends Controller
{
    #[OA\Get(
        path: '/books',
        summary: 'Liste paginée des livres',
        description: 'Retourne 2 livres par page avec liens HATEOAS.',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste paginée',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Book')),
                        new OA\Property(property: 'links', type: 'object'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index()
    {
        return BookResource::collection(Book::paginate(2));
    }

    #[OA\Post(
        path: '/books',
        summary: 'Créer un livre',
        tags: ['Books'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Livre créé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Book'),
                    ]
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'min:3', 'max:255'],
            'author'  => ['required', 'string', 'min:3', 'max:100'],
            'summary' => ['required', 'string', 'min:10', 'max:500'],
            'isbn'    => ['required', 'string', 'size:13', 'unique:books,isbn'],
        ]);

        $book = Book::create($data);

        return new BookResource($book);
    }

    #[OA\Get(
        path: '/books/{book}',
        summary: 'Afficher un livre',
        description: 'Détails du livre. Résultat mis en cache 60 minutes.',
        tags: ['Books'],
        parameters: [
            new OA\Parameter(name: 'book', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Livre trouvé',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Book'),
                    ]
                )
            ),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function show(Book $book)
    {
        $cached = Cache::remember(
            "book.{$book->id}",
            3600,
            fn () => $book
        );

        return new BookResource($cached);
    }

    #[OA\Put(
        path: '/books/{book}',
        summary: 'Modifier un livre (remplacement complet)',
        tags: ['Books'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'book', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Livre mis à jour',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Book'),
                    ]
                )
            ),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    #[OA\Patch(
        path: '/books/{book}',
        summary: 'Modifier partiellement un livre',
        tags: ['Books'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'book', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BookInput')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Livre mis à jour'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationFailed'),
        ]
    )]
    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'title'   => ['sometimes', 'string', 'min:3', 'max:255'],
            'author'  => ['sometimes', 'string', 'min:3', 'max:100'],
            'summary' => ['sometimes', 'string', 'min:10', 'max:500'],
            'isbn'    => ['sometimes', 'string', 'size:13', Rule::unique('books', 'isbn')->ignore($book->id)],
        ]);

        $book->update($data);

        Cache::forget("book.{$book->id}");

        return new BookResource($book);
    }

    #[OA\Delete(
        path: '/books/{book}',
        summary: 'Supprimer un livre',
        tags: ['Books'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'book', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Livre supprimé (no content)'),
            new OA\Response(response: 401, ref: '#/components/responses/Unauthenticated'),
            new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        ]
    )]
    public function destroy(Book $book)
    {
        $id = $book->id;

        Book::destroy($id);

        Cache::forget("book.{$id}");

        return response()->noContent();
    }
}
