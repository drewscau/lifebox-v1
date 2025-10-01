<?php

namespace App\Http\Controllers;

use App\Exceptions\Retailer\DefaultRetailerException;
use App\Models\Retailer;
use App\Services\RetailerService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RetailerController
{
    use ValidatesRequests;

    /**
     * List all retailers
     *
     * @authenticated
     * @group Admin
     * @queryParam length int number of records per page, defaults to 10
     * @queryParam search string company name to search for, lists all if not specified
     * @queryParam column string column to use for ordering the retailers, defaults to ID
     * @queryParam direction string can be one of: asc, desc. Order of records displayed defaults to ascending order
     * @param Request $request
     * @param RetailerService $retailerService
     * @return JsonResponse
     */
    public function index(Request $request, RetailerService $retailerService)
    {
        return response()->json(
            $retailerService->search(
                (int) $request->query('length', 10),
                $request->query('search', null),
                $request->query('column', 'id'),
                $request->query('direction', 'asc')
            )
        );
    }

    /**
     * Create a retailer
     *
     * @authenticated
     * @group Admin
     * @bodyParam company string required
     * @bodyParam status string required one of: active, inactive
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate(
            [
                'company' => ['required', 'unique:retailers,company'],
                'status' => ['required', Rule::in([Retailer::STATUS_ACTIVE, Retailer::STATUS_INACTIVE])],
            ]
        );

        return response()->json(Retailer::create($data), Response::HTTP_CREATED);
    }

    /**
     * Delete a retailer
     *
     * @authenticated
     * @group Admin
     * @urlParam retailer_id int required
     * @param Retailer $retailer
     * @return JsonResponse
     * @throws DefaultRetailerException
     */
    public function destroy(Retailer $retailer)
    {
        if ($retailer->company === Retailer::DEFAULT_RETAILER_COMPANY) {
            throw new DefaultRetailerException(
                'Default Retailer Company ' . Retailer::DEFAULT_RETAILER_COMPANY . ' cannot be deleted'
            );
        }

        $retailer->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Load a single retailer
     *
     * @authenticated
     * @group Admin
     * @urlParam retailer_id int required
     * @param Retailer $retailer
     * @return JsonResponse
     */
    public function show(Retailer $retailer)
    {
        return response()->json($retailer);
    }
    /**
     * Update a retailer
     *
     * @authenticated
     * @group Admin
     * @bodyParam company string company name
     * @bodyParam status string can be one of: active,inactive
     * @param Request $request
     * @param Retailer $retailer
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, Retailer $retailer)
    {
        if ($retailer->company === Retailer::DEFAULT_RETAILER_COMPANY) {
            throw new DefaultRetailerException('Not allowed to update default retailer.');
        }

        $this->validate(
            $request,
            [
                'company' => ['sometimes', 'string'],
                'status' => ['sometimes', Rule::in([Retailer::STATUS_ACTIVE, Retailer::STATUS_INACTIVE])],
            ]
        );

        $retailer->company = $request->input('company', $retailer->company);
        $retailer->status = $request->input('status', $retailer->status);
        $retailer->save();

        return response()->json($retailer);
    }
}
