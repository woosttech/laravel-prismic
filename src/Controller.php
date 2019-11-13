<?php

namespace Woost\LaravelPrismic;

use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Http\Request;

class Controller extends LaravelController
{

    public function show(Request $request)
    {
        $route = $request->route()->action['as'];
        $customType = Facade::classForTypeName(explode('.', $route)[1]);
        if (!$customType) {
            abort(404);
        }

        $params = $request->route()->parameters();

        // Find page by UID
        if (!empty($params['uid']) && $page = $customType::find($params['uid'])) {
            return view($customType::getViewName(), [
                'page' => $page
            ]);
        }

        // Temp fix
        abort(404);

        // Find page by where clauses
        $results = $customType::where($params);
        if ($results->isEmpty()) {
            abort(404);
        }

        if (count($results) === 1) {
            $page = $results[0];

            return view($customType::getViewName(), [
                'page' => $page,
            ]);
        }

        // Multiple results, show lister? For now show first
        $page = $results->first();

        return view($customType::getViewName(), [
            'page' => $page,
        ]);
    }

}
