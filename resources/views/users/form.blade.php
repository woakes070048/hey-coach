<div class="form-group">
    {{ Form::label('image_path', __('Avatar'), ['class' => 'control-label']) }}
    {!! Form::file('image_path',  null, ['class' => 'form-control']) !!}
</div>


<div class="form-group">
    {!! Form::label('name', __('Name'), ['class' => 'control-label']) !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group">
    {!! Form::label('email', __('Mail'), ['class' => 'control-label']) !!}
    {!! Form::email('email', null, ['class' => 'form-control']) !!}
</div>

<div class="form-group">
    {!! Form::label('work_number', __('Phone number'), ['class' => 'control-label']) !!}
    {!! Form::text('work_number',  null, ['class' => 'form-control']) !!}
</div>

<div class="form-group">
    {!! Form::label('password', __('Password'), ['class' => 'control-label']) !!}
    {!! Form::password('password', ['class' => 'form-control']) !!}
</div>
<div class="form-group">
    {!! Form::label('password_confirmation', __('Confirm password'), ['class' => 'control-label']) !!}
    {!! Form::password('password_confirmation', ['class' => 'form-control']) !!}
</div>
<div class="form-group form-inline">
    {!! Form::label('roles', __('Assign role'), ['class' => 'control-label']) !!}
    {!!
        Form::select('roles',
        $roles,
        isset($user->role->role_id) ? $user->role->role_id : null,
        ['class' => 'form-control']) !!}

    {!! Form::label('colleges', __('Assign college'), ['class' => 'control-label']) !!}
    {!!
        Form::select('colleges',
        $colleges,
        isset($user->college->college_id) ? $user->college->college_id : null,
        ['class' => 'form-control']) !!}

    {!! Form::label('departments', __('Assign department'), ['class' => 'control-label']) !!}

    {!!
        Form::select('departments',
        $departments,
        isset($user)
        ? $user->department->first()->id : null,
        ['class' => 'form-control']) !!}
</div>

{!! Form::submit($submitButtonText, ['class' => 'btn btn-primary']) !!}
