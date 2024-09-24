<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Requests\IndexBookRequest;
use Illuminate\Validation\ValidationException;

class BookController extends Controller
{
    private function getBooksCacheVersion()
    {
        return Cache::rememberForever('books_cache_version', function () {
            return 1;
        });
    }

    private function incrementBooksCacheVersion()
    {
        Cache::increment('books_cache_version');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexBookRequest $request): JsonResponse
    {
        $sortField = $request->input('sort_field');
        $sortOrder = $request->input('sort_order');
        $perPage = $request->input('per_page');
        $search = $request->input('search');
        $publishDateFrom = $request->input('publish_date_from');
        $publishDateTo = $request->input('publish_date_to');
        $fields = $request->input('fields');

        $cacheVersion = $this->getBooksCacheVersion();
        $cacheKey = "books_v{$cacheVersion}_page_" . request('page', 1) . "_{$sortField}_{$sortOrder}_{$perPage}_{$search}_{$publishDateFrom}_{$publishDateTo}_{$fields}";

        $books = Cache::remember($cacheKey, 300, function () use ($sortField, $sortOrder, $perPage, $search, $publishDateFrom, $publishDateTo, $fields) {
            $query = Book::with('author:id,name')->select(explode(',', $fields));

            if (!empty($search)) {
                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            }

            if ($publishDateFrom && $publishDateTo) {
                $query->whereBetween('publish_date', [$publishDateFrom, $publishDateTo]);
            } elseif ($publishDateFrom) {
                $query->whereDate('publish_date', '>=', $publishDateFrom);
            } elseif ($publishDateTo) {
                $query->whereDate('publish_date', '<=', $publishDateTo);
            }

            return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
        });

        return response()->json([
            'data' => $books->items(),
            'current_page' => $books->currentPage(),
            'last_page' => $books->lastPage(),
            'per_page' => $books->perPage(),
            'total' => $books->total(),
            'links' => [
                'first' => $books->url(1),
                'last' => $books->url($books->lastPage()),
                'prev' => $books->previousPageUrl(),
                'next' => $books->nextPageUrl(),
            ],
            'status' => 200
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        try {
            // Use the validated data from the StoreBookRequest
            $validated = $request->validated();

            $book = Book::create($validated);

            // Clear the cache for the list of books
            $this->incrementBooksCacheVersion();

            return response()->json(['data' => $book, 'status' => 201], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create book: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create book.', 'status' => 500], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $fields = $request->input('fields', '*'); // Default to all fields
            $book = Cache::remember('book_' . $id . '_' . $fields, 3600, function () use ($id, $fields) {
                return Book::with('author')->select(explode(',', $fields))->findOrFail($id);
            });
            return response()->json(['data' => $book, 'status' => 200], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Book not found.', 'status' => 404], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve book.', 'status' => 500], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, $id): JsonResponse
    {
        try {
            $book = Book::findOrFail($id);

            $book->update($request->validated());

            // Clear the cache for the updated book and the list of books
            Cache::forget('book_' . $id);
            $this->incrementBooksCacheVersion();

            return response()->json(['data' => $book, 'status' => 200], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Book not found.', 'status' => 404], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update book: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update book.', 'status' => 500], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $book = Book::findOrFail($id);
            $book->delete();

            // Clear the cache for the deleted book and the list of books
            Cache::forget('book_' . $id);
            $this->incrementBooksCacheVersion();

            return response()->json(['status' => 204], 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Book not found.', 'status' => 404], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete book: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete book.', 'status' => 500], 500);
        }
    }
}