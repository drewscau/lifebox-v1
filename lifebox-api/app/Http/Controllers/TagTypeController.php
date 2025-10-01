<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TagType;
use Symfony\Component\Console\Output\ConsoleOutput;

class TagTypeController extends Controller
{
    public function index()
    {
        // $out = new ConsoleOutput();
        // $out->writeln("tagtypes: ");

        $types = TagType::all();
        $data = [];

        foreach ($types as $type) {
            $data[] = [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
            ];
        }

        return response()->json($data);
    }
}
