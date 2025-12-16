<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchTranslationsRequest;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TranslationService $translationService
    ) {}

    /**
     * Display a listing of translations.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'locale' => $request->input('locale'),
            'key' => $request->input('key'),
            'tags' => $request->input('tags', []),
        ];

        $perPage = $request->input('per_page', 15);

        $translations = $this->translationService->getAllTranslations(
            array_filter($filters),
            $perPage
        );

        return response()->json([
            'success' => true,
            'data' => $translations->items(),
            'meta' => [
                'current_page' => $translations->currentPage(),
                'last_page' => $translations->lastPage(),
                'per_page' => $translations->perPage(),
                'total' => $translations->total(),
            ],
        ]);
    }

    /**
     * Store a newly created translation.
     */
    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->translationService->createTranslation($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Translation created successfully.',
            'data' => $translation,
        ], 201);
    }

    /**
     * Display the specified translation.
     */
    public function show(int $id): JsonResponse
    {
        $translation = $this->translationService->getTranslation($id);

        if (!$translation) {
            return response()->json([
                'success' => false,
                'message' => 'Translation not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $translation,
        ]);
    }

    /**
     * Update the specified translation.
     */
    public function update(UpdateTranslationRequest $request, int $id): JsonResponse
    {
        $updated = $this->translationService->updateTranslation($id, $request->validated());

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Translation not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Translation updated successfully.',
        ]);
    }

    /**
     * Remove the specified translation.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->translationService->deleteTranslation($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Translation not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Translation deleted successfully.',
        ]);
    }

    /**
     * Search translations.
     */
    public function search(SearchTranslationsRequest $request): JsonResponse
    {
        $translations = $this->translationService->searchTranslations(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => $translations->items(),
            'meta' => [
                'current_page' => $translations->currentPage(),
                'last_page' => $translations->lastPage(),
                'per_page' => $translations->perPage(),
                'total' => $translations->total(),
            ],
        ]);
    }

    /**
     * Export translations.
     */
    public function export(Request $request): JsonResponse
    {
        $locale = $request->input('locale');
        $tags = $request->input('tags');

        $translations = $this->translationService->exportTranslations($locale, $tags);

        $response = response()->json([
            'success' => true,
            'data' => $translations,
            'meta' => [
                'locale' => $locale,
                'tags' => $tags,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);

        // Add CDN headers if enabled
        if (config('translation.cdn_enabled')) {
            $response->header('Cache-Control', 'public, max-age=3600')
                ->header('CDN-Cache-Control', 'public, max-age=86400')
                ->header('Surrogate-Control', 'max-age=86400');
        }

        return $response;
    }

    /**
     * Get available locales.
     */
    public function locales(): JsonResponse
    {
        $locales = $this->translationService->getAvailableLocales();

        return response()->json([
            'success' => true,
            'data' => $locales,
        ]);
    }
}
