<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Requests\IndexAuthorRequest;
use Illuminate\Validation\ValidationException;

class AuthorController extends Controller
{
    private function getAuthorsCacheVersion()
    {
        return Cache::rememberForever('authors_cache_version', function () {
            return 1;
        });
    }

    private function incrementAuthorsCacheVersion()
    {
        Cache::increment('authors_cache_version');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexAuthorRequest $request): JsonResponse
    {
        $sortField = $request->input('sort_field');
        $sortOrder = $request->input('sort_order');
        $perPage = $request->input('per_page');
        $search = $request->input('search');
        $fields = $request->input('fields');

        $cacheVersion = $this->getAuthorsCacheVersion();
        $cacheKey = "authors_v{$cacheVersion}_page_" . request('page', 1) . "_{$sortField}_{$sortOrder}_{$perPage}_{$search}_{$fields}";

        $authors = Cache::remember($cacheKey, 300, function () use ($sortField, $sortOrder, $perPage, $search, $fields) {
            $query = Author::select(explode(',', $fields));

            if (!empty($search)) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('bio', 'like', "%{$search}%");
            }

            return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
        });

        return response()->json([
            'data' => $authors->items(),
            'current_page' => $authors->currentPage(),
            'last_page' => $authors->lastPage(),
            'per_page' => $authors->perPage(),
            'total' => $authors->total(),
            'links' => [
                'first' => $authors->url(1),
                'last' => $authors->url($authors->lastPage()),
                'prev' => $authors->previousPageUrl(),
                'next' => $authors->nextPageUrl(),
            ],
            'status' => 200
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuthorRequest $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'bio' => 'required|string',
                'birth_date' => 'required|date_format:Y-m-d',
            ]);

            $author = Author::create($validated);

            // Clear the cache for the list of authors
            $this->incrementAuthorsCacheVersion();

            return response()->json($author, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
                'status' => 422
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $fields = $request->input('fields', '*'); // Default to all fields
            $author = Cache::remember('author_' . $id . '_' . $fields, 3600, function () use ($id, $fields) {
                return Author::select(explode(',', $fields))->findOrFail($id);
            });
            return response()->json(['data' => $author, 'status' => 200], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Author not found.', 'status' => 404], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve author.', 'status' => 500], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuthorRequest $request, $id): JsonResponse
    {
        try {
            $author = Author::findOrFail($id);

            $author->update($request->validated());

            // Clear the cache for the updated author and the list of authors
            Cache::forget('author_' . $author->id);
            $this->incrementAuthorsCacheVersion();

            return response()->json(['data' => $author, 'status' => 200], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Author not found.', 'status' => 404], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update author: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update author.', 'status' => 500], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $author = Author::findOrFail($id);
            $author->delete();

            // Clear the cache for the deleted author and the list of authors
            Cache::forget('author_' . $author->id);
            $this->incrementAuthorsCacheVersion();

            return response()->json(['status' => 204], 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Author not found.', 'status' => 404], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete author: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete author.', 'status' => 500], 500);
        }
    }

    /**
     * Get books by author.
     */
    public function books($id): JsonResponse
    {
        try {
            // Check if the author exists
            $author = Author::findOrFail($id);

            // Cache key
            $cacheKey = 'author_books_' . $author->id . '_page_' . request('page', 1);

            // Retrieve books from cache or database
            $books = Cache::remember($cacheKey, 3600, function () use ($author) {
                return $author->books()
                    ->select('id', 'title', 'publish_date', 'author_id')
                    ->with('author:id,name')
                    ->paginate(20);
            });

            // Check if books are retrieved
            if ($books->isEmpty()) {
                return response()->json(['error' => 'No books found for this author.', 'status' => 404], 404);
            }

            return response()->json(['data' => $books, 'status' => 200], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Author not found.', 'status' => 404], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve books.', 'status' => 500], 500);
        }
    }

    public function getBooks($id)
    {
        $author = Author::findOrFail($id);
        $books = $author->books()->get();

        return response()->json([
            'data' => $books,
            'status' => 200
        ], 200);
    }
}

