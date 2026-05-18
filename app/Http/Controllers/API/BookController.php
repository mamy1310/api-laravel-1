<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index()
    {
        return BookResource::collection(Book::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'min:3', 'max:255'],
            'author'  => ['required', 'string', 'min:3', 'max:100'],
            'summary' => ['required', 'string', 'min:10', 'max:500'],
            'isbn'    => ['required', 'string', 'size:13', 'unique:books,isbn'],
        ]);

        $book = Book::create($data);

        return (new BookResource($book))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Book $book)
    {
        return new BookResource($book);
    }

    public function update(Request $request, Book $book)
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'min:3', 'max:255'],
            'author'  => ['required', 'string', 'min:3', 'max:100'],
            'summary' => ['required', 'string', 'min:10', 'max:500'],
            'isbn'    => ['required', 'string', 'size:13', Rule::unique('books', 'isbn')->ignore($book->id)],
        ]);

        $book->update($data);

        return new BookResource($book);
    }

    public function destroy(Book $book)
    {
        Book::destroy($book->id);

        return response()->noContent();
    }
}
