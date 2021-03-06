<?php
namespace App\Http\Controllers;

use Gate;
use Carbon;
use Datatables;
use Session;
use App\Models\Task;
use App\Http\Requests;
use App\Models\Integration;
use Illuminate\Http\Request;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Repositories\Task\TaskRepositoryContract;
use App\Repositories\User\UserRepositoryContract;
use App\Repositories\Athlete\AthleteRepositoryContract;
use App\Repositories\Setting\SettingRepositoryContract;
use App\Repositories\Invoice\InvoiceRepositoryContract;

class TasksController extends Controller
{

    protected $request;
    protected $tasks;
    protected $athletes;
    protected $settings;
    protected $users;
    protected $invoices;

    public function __construct(
        TaskRepositoryContract $tasks,
        UserRepositoryContract $users,
        AthleteRepositoryContract $athletes,
        InvoiceRepositoryContract $invoices,
        SettingRepositoryContract $settings
    )
    {
        $this->tasks = $tasks;
        $this->users = $users;
        $this->athletes = $athletes;
        $this->invoices = $invoices;
        $this->settings = $settings;

        $this->middleware('task.create', ['only' => ['create']]);
        $this->middleware('task.update.status', ['only' => ['updateStatus']]);
        $this->middleware('task.assigned', ['only' => ['updateAssign', 'updateTime']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('tasks.index');
    }

    public function anyData()
    {
        $tasks = Task::select(
            ['id', 'title', 'created_at', 'deadline', 'user_assigned_id']
        )
            ->where('status', 1)->get();
        return Datatables::of($tasks)
            ->addColumn('titlelink', function ($tasks) {
                return '<a href="tasks/' . $tasks->id . '" ">' . $tasks->title . '</a>';
            })
            ->editColumn('created_at', function ($tasks) {
                return $tasks->created_at ? with(new Carbon($tasks->created_at))
                    ->format('d/m/Y') : '';
            })
            ->editColumn('deadline', function ($tasks) {
                return $tasks->created_at ? with(new Carbon($tasks->deadline))
                    ->format('d/m/Y') : '';
            })
            ->editColumn('user_assigned_id', function ($tasks) {
                return $tasks->user->name;
            })->make(true);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return mixed
     */
    public function create()
    {
        $college_id = Session::get('college_id');
        return view('tasks.create')
            ->withUsers($this->users->getAllUsersForCollegeWithDepartments($college_id))
            ->withathletes($this->athletes->listAllathletes());
    }

    /**
     * @param StoreTaskRequest $request
     * @return mixed
     */
    public function store(StoreTaskRequest $request) // uses __contrust request
    {
        $college_id = Session::get('college_id');
        $request->merge(['college_id' => $college_id]);
        $getInsertedId = $this->tasks->create($request);
        return redirect()->route("tasks.show", $getInsertedId);
    }


    /**
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function show(Request $request, $id)
    {
        $college_id = Session::get('college_id');
        return view('tasks.show')
            ->withTasks($this->tasks->find($id))
            ->withUsers($this->users->getAllUsersForCollegeWithDepartments($college_id))
            ->withInvoiceLines($this->tasks->getInvoiceLines($id))
            ->withCompanyname($this->settings->getCompanyName());
    }


    /**
     * Sees if the Settings from backend allows all to complete taks
     * or only assigned user. if only assigned user:
     * @param $id
     * @param Request $request
     * @return
     * @internal param $ [Auth]  $id Checks Logged in users id
     * @internal param $ [Model] $task->user_assigned_id Checks the id of the user assigned to the task
     * If Auth and user_id allow complete else redirect back if all allowed excute
     * else stmt
     */
    public function updateStatus($id, Request $request)
    {
        $this->tasks->updateStatus($id, $request);
        Session()->flash('flash_message', 'Task is completed');
        return redirect()->back();
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function updateAssign($id, Request $request)
    {
        $athleteId = $this->tasks->getAssignedathlete($id)->id;


        $this->tasks->updateAssign($id, $request);
        Session()->flash('flash_message', 'New user is assigned');
        return redirect()->back();
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function updateTime($id, Request $request)
    {
        $this->tasks->updateTime($id, $request);
        Session()->flash('flash_message', 'Time has been updated');
        return redirect()->back();
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function invoice($id, Request $request)
    {
        $task = Task::findOrFail($id);
        $athleteId = $task->athlete()->first()->id;
        $timeTaskId = $task->time()->get();
        $integrationCheck = Integration::first();

        if ($integrationCheck) {
            $this->tasks->invoice($id, $request);
        }
        $this->invoices->create($athleteId, $timeTaskId, $request->all());
        Session()->flash('flash_message', 'Invoice created');
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     * @return mixed
     * @internal param int $id
     */
    public function marked()
    {
        Notifynder::readAll(\Auth::id());
        return redirect()->back();
    }
}
