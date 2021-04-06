@extends('admin.layouts.sidebar')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Profile Details</h3>
                </div>

                <form class="form-horizontal" id="validateForm" method="POST" action="{{ url('profile') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="id" value="{{$user->id ?? ''}}" />
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group row col-md-12">
                                <label for="profile_image" class="col-sm-3 col-form-label">Profile Image</label>
                                <div class="col-sm-9">
                                    @if(isset($user->profile_image) && !empty($user->profile_image))
                                    <img src="{{$user->profile_image}}" width="100" height="100" />
                                    <button type="button" class="btn open-modal dlt-btn" data-id="{{$user->id}}"><i class="fas fa-trash"></i></button>
                                    @else
                                    <input type="file" name="profile_image" id="profile_image" />
                                    @endif
                                    @if(!empty($errors->first('profile_image')))
                                    <small class="form-text text-danger">{!! $errors->first('profile_image') !!}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group row col-md-12">
                                <label for="first_name" class="col-sm-3 col-form-label">First Name</label>
                                <div class="col-sm-9">
                                    <input type="text" name="first_name" id="first_name" value="{{$user->first_name ?? old('first_name') ?? ''}}" class="form-control {{ $errors->has('first_name') ? 'border-danger' : ''}}" />
                                    @if(!empty($errors->first('first_name')))
                                    <small class="form-text text-danger">{!! $errors->first('first_name') !!}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group row col-md-12">
                                <label for="last_name" class="col-sm-3 col-form-label">Last Name</label>
                                <div class="col-sm-9">
                                    <input type="text" name="last_name" id="last_name" value="{{$user->last_name ?? old('last_name') ?? ''}}" class="form-control {{ $errors->has('last_name') ? 'border-danger' : ''}}" />
                                    @if(!empty($errors->first('last_name')))
                                    <small class="form-text text-danger">{!! $errors->first('last_name') !!}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group row col-md-12">
                                <label for="email" class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="email" name="email" value="{{$user->email ?? old('email') ?? ''}}" class="form-control {{ $errors->has('email') ? 'border-danger' : ''}}" readonly />
                                    @if(!empty($errors->first('email')))
                                    <small class="form-text text-danger">{!! $errors->first('email') !!}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-info float-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('page-script')
<script>
    $(document).ready(function() {
        $(".dlt-btn").click(function() {
            var id = $(this).data("id");

            $.ajax({
                url: "/profileImageDelete/" + id,
                type: 'post',
                data: {
                    "id": id,
                },
                success: function() {
                    window.location.reload();
                },
                error: function(xhr) {
                    window.location.reload();
                }
            });

        });
    });
</script>
@if(session()->has('success'))
<script type="text/javascript">
    toastr.success('<?php echo session()->get('success'); ?>')
</script>
@endif
@if ($errors->any())
@foreach ($errors->all() as $error)
<script type="text/javascript">
    toastr.error('{{$error}}')
</script>
@endforeach
@endif
@endsection