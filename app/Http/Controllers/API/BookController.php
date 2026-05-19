<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class BookController extends Controller
{
    public function index()
    {
        return BookResource::collection(Book::paginate(2));
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

        return new BookResource($book);
    }

    public function show(Book $book)
    {
        $cached = Cache::remember(
            "book.{$book->id}",
            3600,
            fn () => $book
        );

        return new BookResource($cached);
    }

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

    public function destroy(Book $book)
    {
        $id = $book->id;

        Book::destroy($id);

        Cache::forget("book.{$id}");

        return response()->noContent();
    }
}
