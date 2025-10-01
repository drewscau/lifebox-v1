<?php

namespace App\Services;

use App\Exceptions\Retailer\MissingDefaultRetailerException;
use App\Models\Retailer;

class RetailerService
{
    /**
     * Helper function for searching the retailers
     */
    public function search($limit, $searchText, $sortedColumn, $sortDirection = 'asc')
    {
        $retailers = Retailer::where(function ($query) use ($searchText) {
            if ($searchText) {
                $query->where('company', 'like', '%' . $searchText . '%');
            }
            $query->where('company', '<>', Retailer::DEFAULT_RETAILER_COMPANY);
        });

        return $retailers->orderBy($sortedColumn, $sortDirection)->paginate($limit);
    }

    /**
     * @return mixed
     * @throws MissingDefaultRetailerException
     */
    public function getDefaultRetailerId()
    {
        if ($retailer = Retailer::where('company', Retailer::DEFAULT_RETAILER_COMPANY)->first()) {
            return $retailer->id;
        }

        throw new MissingDefaultRetailerException(
            'Unable to find a default retailer: ' . Retailer::DEFAULT_RETAILER_COMPANY
        );
    }
}
