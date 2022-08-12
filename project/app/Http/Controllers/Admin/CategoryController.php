<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;
use Validator;
use Datatables;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    //*** JSON Request
    public function datatables($type)
    {
         $datas = Category::when($type == 'feat', function ($query) {
                                return $query->where('featured', 1);
                            })->orderBy('id','desc')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('status', function(Category $data) {
                                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                                $s = $data->status == 1 ? 'selected' : '';
                                $ns = $data->status == 0 ? 'selected' : '';
                                return '<div class="action-list"><select class="process select droplinks '.$class.'"><option data-val="1" value="'. route('admin-cat-status',['id1' => $data->id, 'id2' => 1]).'" '.$s.'>Active</option><<option data-val="0" value="'. route('admin-cat-status',['id1' => $data->id, 'id2' => 0]).'" '.$ns.'>Deactive</option>/select></div>';
                            })
                            ->addColumn('action', function(Category $data) {
                                return '<div class="action-list"><a data-href="' . route('admin-cat-edit',$data->id) . '" class="edit" data-toggle="modal" data-target="#modal1"> <i class="fas fa-edit"></i>Edit</a><a href="javascript:;" data-href="' . route('admin-cat-delete',$data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a></div>';
                            })
                            ->rawColumns(['status','action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function index()
    {
        $data['type'] = 'all';
        return view('admin.category.index', $data);
    }

    //*** GET Request
    public function create()
    {
        return view('admin.category.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'slug' => 'nullable|unique:categories|regex:/^[a-zA-Z0-9\s-]+$/',
        ];
        $customs = [
            'photo.mimes' => 'Image Type is Invalid.',
            'slug.unique' => 'This slug has already been taken.',
            'slug.regex' => 'Slug Must Not Have Any Special Characters.'
        ];
        $validator = Validator::make(Input::all(), $rules, $customs);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $input = $request->all();
        if ($file = $request->file('photo'))
         {
            $name = time().$file->getClientOriginalName();
            $file->move('assets/images/categories',$name);
            $input['photo'] = $name;
        }

        $cat = Category::create($input);
        //--- Logic Section Ends

        if (!$request->filled('slug')) {
           $cat->slug = Str::slug($request->name, '-') . '-' . Str::random(5) .'-'. $cat->id;
           $cat->save();
        }

        //--- Redirect Section
        $msg = 'New Data Added Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function edit($id)
    {
        $data = Category::findOrFail($id);
        return view('admin.category.edit',compact('data'));
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
          'photo' => 'mimes:jpeg,jpg,png,svg',
          'slug' => 'unique:categories,slug,'.$id.'|regex:/^[a-zA-Z0-9\s-]+$/'
             ];
        $customs = [
          'photo.mimes' => 'Image Type is Invalid.',
          'slug.unique' => 'This slug has already been taken.',
          'slug.regex' => 'Slug Must Not Have Any Special Characters.'
        ];
        $validator = Validator::make(Input::all(), $rules, $customs);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = Category::findOrFail($id);
        $input = $request->all();
            if ($file = $request->file('photo'))
            {
                $name = time().$file->getClientOriginalName();
                $file->move('assets/images/categories',$name);
                if($data->photo != null)
                {
                    if (file_exists(public_path().'/assets/images/categories/'.$data->photo)) {
                        unlink(public_path().'/assets/images/categories/'.$data->photo);
                    }
                }
            $input['photo'] = $name;
            }
        $data->update($input);
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = 'Data Updated Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

      //*** GET Request Status
      public function status($id1,$id2)
        {
            $data = Category::findOrFail($id1);
            $data->status = $id2;
            $data->update();
        }


    //*** GET Request Delete
    public function destroy($id)
    {
        $data = Category::findOrFail($id);

        if($data->cars->count()>0)
        {
        //--- Redirect Section
        $msg = 'Remove the cars first !!!!';
        return response()->json($msg);
        //--- Redirect Section Ends
        }

        $data->delete();
        //--- Redirect Section
        $msg = 'Data Deleted Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }
}
