@extends('layouts.main')
@section('container')
<div class="d-sm-flex align-items-center justify-content-between">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">User</li>
    </ol>
</div>
<div class="row">
    <!-- DataTable with Hover -->
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="table-responsive p-3">
                {{-- <button class="btn btn-success mb-3" onclick="add()"><i class="fas fa-plus mr-2"></i> Add</button> --}}
                <a class="btn btn-success mb-3" href="/user/export" target="_blank"><i class="fas fa-file-excel"></i> Export</a>
                <table class="table align-items-center table-flush table-hover" id="dataTable">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Nomer Telepon</th>
                            <th>Peran</th>
                            <th>Diblok?</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modal_form" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="title_form"></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div id="box_msg_user"></div>
				<form id="form_user" autocomplete="nope">

				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" id="btnSave" onclick="save()">Save</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal_detail" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="title_detail"></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="body_detail">
			</div>
			<div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal_delete" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="title_delete"></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="body_delete">

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" id="btnHapus">Delete</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<!--Row-->
<!-- Scroll to top -->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>


<!-- Page level custom scripts -->
<script>
var table;
var save_method;

function isNumber(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if ( (charCode > 31 && charCode < 48) || charCode > 57) {
        return false;
    }
    return true;
}

$(document).ready(function () {
    // $('#dataTable').DataTable(); // ID From dataTable with Hover
    table = $('#dataTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "{{ url('/user/data') }}",
            "data": {
                "_token": "{{ csrf_token() }}"
            },
            "type": "POST",
            "dataType": "json",
        },
        "order": [[ 1, 'asc' ]], //Initial no order.

        "columnDefs": [
            {
                "targets": [ 0,6 ], //last column
                "orderable": false, //set not orderable
            },
            { "targets": 6, "width": '120px' },
            {"targets": 0, "width": '20px'}
        ],
    });
});

function reload_table()
{
    table.ajax.reload(null,false); //reload datatable ajax
}

function add()
{
    save_method = 'add';
    $('#form_user').html(""); // reset form on modals
    $('#body_detail').html("");
    $("#box_msg_user").html('').hide();
    $('#btnSave').text('Save');
    $('#btnSave').attr('disabled',false);
    $('#title_form').text('Add {{ $title }}'); // Set Title to Bootstrap modal title

    $.ajax({
        url : "{{ url('/user/add-view') }}",
        type: "GET",
        dataType: "JSON",
        success: async function(data, textStatus, xhr)
        {
            if(xhr.status == '200'){
                await $('#form_user').html(data.html);
                await $("#birthday").datepicker({
                    format: 'yyyy-mm-dd',
                    autoclose: true
                });
                var id = $('#provinces').val();
                if(id != ""){
                    await relod_regions('provinces');
                }

                await hide_other(data.iwf_events, true)

                await show_occupation()
            }else{
                toastr.error(xhr.statusText);
            }
            $('#modal_form').modal('show'); // show bootstrap modal
        }
    });
}

function edit(id)
{
    save_method = 'edit';
    $('#form_user').html(""); // reset form on modals
    $('#body_detail').html("");
    $("#box_msg_user").html('').hide();
    $('#btnSave').text('Save');
    $('#btnSave').attr('disabled',false);
    $('#title_form').text('Edit {{ $title }}'); // Set Title to Bootstrap modal title

    $.ajax({
        url : "{{ url('/user/edit-view') }}/" + id,
        type: "GET",
        dataType: "JSON",
        success: async function(data, textStatus, xhr)
        {
            if(xhr.status == '200'){
                await $('#form_user').html(data.html);
                await $("#birthday").datepicker({
                    format: 'yyyy-mm-dd',
                    autoclose: true
                });

                var id = $('#provinces').val();
                if(id != ""){
                    await relod_regions('provinces', data.detail);
                }

                await hide_other(data.iwf_events, true)

                await show_occupation_edit(data.detail)

            }else{
                toastr.error(xhr.statusText);
            }

            $('#modal_form').modal('show'); // show bootstrap modal

        }
    });
}

function save()
{
    $('#btnSave').text('Process...'); //change button text
    $('#btnSave').attr('disabled',true); //set button disable
    var url;

    if(save_method == 'add') {
        url = "{{ url('/user/add') }}";
    } else {
        url = "{{ url('/user/edit') }}";
    }

    var data_form = $('#form_user').serialize()+ "&" + $.param({_token:"{{ csrf_token() }}"});
    $.ajax({
        url : url,
        type: "POST",
        data: data_form,
        dataType: "json",
        success: async function(data, textStatus, xhr)
        {
            if(xhr.status == '200'){
                if(data.status)
                {
                    $('#modal_form').modal('toggle');
                    $("#box_msg_user").html('').hide();
                    await reload_table();
                    await toastr.success(data.message);
                }
                else
                {
                    await $('#box_msg_user').html(data.message).fadeOut().fadeIn();
                    $('#modal_form').animate({ scrollTop: 0 }, 'slow');
                }
            }else{
                $('#modal_form').modal('toggle');
                toastr.error(xhr.statusText);
            }

            $('#btnSave').text('Save');
            $('#btnSave').attr('disabled',false);

        }
    });
}

function detail(id)
{
    $('#title_detail').text('Detail {{ $title }}'); // Set Title to Bootstrap modal title
    $('#form_user').html("");

    $.ajax({
        url : "{{ url('/user/detail') }}/" + id,
        type: "GET",
        dataType: "JSON",
        success: async function(data, textStatus, xhr)
        {
            if(xhr.status == '200'){
                $('#body_detail').html(data.html);
                await show_occupation_detail(data.detail)
            }else{
                toastr.error(xhr.statusText);
            }

            $('#modal_detail').modal('show'); // show bootstrap modal

        }
    });
}

function deletes(id,name)
{
    $('#modal_delete').modal('show'); // show bootstrap modal when complete loaded
    $('#title_delete').text('Delete {{ $title }}'); // Set title to Bootstrap modal title
    $("#body_delete").html('Delete {{ $title }} <b>'+name+'</b> ?');
    $('#btnHapus').attr("onclick", "process_delete('"+id+"')");
}

function process_delete(id)
{
    $('#btnHapus').text('Process...'); //change button text
    $('#btnHapus').attr('disabled',true); //set button disable

    $.ajax({
        url : "{{ url('/user/delete') }}/" + id,
        type: "GET",
        dataType: "JSON",
        success: function(data, textStatus, xhr)
        {
            if(xhr.status == '200'){
                toastr.success(data.message);
                reload_table();
            }else{
                toastr.error(xhr.statusText);
            }

            $('#btnHapus').text('Delete');
            $('#btnHapus').attr('disabled',false);
            $('#modal_delete').modal('toggle');

        }
    });
}

function relod_regions(elm, detail = ""){
    var id = $('#'+elm).val();

    if(id == ""){
        id = 0;
    }

    $.ajax({
        url : "{{ url('/get-region-by-provinces') }}/" + id,
        type: "GET",
        dataType: "JSON",
        success: function(data, textStatus, xhr)
        {
            if(xhr.status == '200'){
                var str = '<option value="">-- Pilih --</option>';
                if(data.length > 0){
                    data.forEach(function(item, index){
                        if(item.id == detail.region_id){
                            selected = "selected";
                        }else{
                            selected = "";
                        }
                        str += '<option value="'+item.id+'" '+selected+'>'+item.name+'</option>';
                    });
                }
                $('#regions').html(str);
            }

        }
    });
}

function show_occupation(){
    var val = $("#occupation").val()

    if(val != ""){
        key = val.toLowerCase();
        key = key.replaceAll(" ", "_");

        $.ajax({
            url : "{{ url('/get-occupation-type') }}/" + key,
            type: "GET",
            dataType: "JSON",
            success: function(data, textStatus, xhr)
            {
                if(xhr.status == '200'){

                    if(data.result.length > 0){
                        var str = '<div class="form-group"><label for="occupation_type_title">'+data.occupation_type_lable[0]+'</label><input class="form-control" type="text" id="occupation_type_title" name="occupation_type_title"></div>';
                        $('#occupation_type_title_cover').html(str).show();

                        var str = '<div class="form-group"><label for="occupation_type">'+data.occupation_type_lable[1]+'</label><select name="occupation_type" class="form-control">';
                        str += '<option value="">-- Pilih --</option>';
                        if(data.result.length > 0){
                            data.result.forEach(function(item, index){
                                str += '<option value="'+item+'">'+item+'</option>';
                            });
                        }
                        str += '</select><div>';
                        $('#occupation_type_cover').html(str).show();
                    }else{
                        $('#occupation_type_title_cover').hide();
                        $('#occupation_type_cover').hide();
                    }
                }

            }
        });
    }
}

function show_occupation_edit(detail){
    var val = $("#occupation").val()

    if(val != ""){
        key = val.toLowerCase();
        key = key.replaceAll(" ", "_");

        $.ajax({
            url : "{{ url('/get-occupation-type') }}/" + key,
            type: "GET",
            dataType: "JSON",
            success: function(data, textStatus, xhr)
            {
                if(xhr.status == '200'){

                    if(data.result.length > 0){
                        var str = '<div class="form-group"><label for="occupation_type_title">'+data.occupation_type_lable[0]+'</label><input class="form-control" type="text" id="occupation_type_title" name="occupation_type_title" value="'+detail.occupation_company_name+'"></div>';
                        $('#occupation_type_title_cover').html(str).show();

                        var str = '<div class="form-group"><label for="occupation_type">'+data.occupation_type_lable[1]+'</label><select name="occupation_type" class="form-control">';
                        str += '<option value="">-- Pilih --</option>';
                        if(data.result.length > 0){
                            data.result.forEach(function(item, index){
                                if(item == detail.occupation_company_detail){
                                    selected = "selected";
                                }else{
                                    selected = "";
                                }
                                str += '<option value="'+item+'" '+selected+'>'+item+'</option>';
                            });
                        }
                        str += '</select><div>';
                        $('#occupation_type_cover').html(str).show();
                    }else{
                        $('#occupation_type_title_cover').hide();
                        $('#occupation_type_cover').hide();
                    }
                }

            }
        });
    }
}

function show_occupation_edit_val(occupation_company_name, occupation_company_detail){
    var val = $("#occupation").val()

    if(val != ""){
        key = val.toLowerCase();
        key = key.replaceAll(" ", "_");

        $.ajax({
            url : "{{ url('/get-occupation-type') }}/" + key,
            type: "GET",
            dataType: "JSON",
            success: function(data, textStatus, xhr)
            {
                if(xhr.status == '200'){

                    if(data.result.length > 0){
                        var str = '<div class="form-group"><label for="occupation_type_title">'+data.occupation_type_lable[0]+'</label><input class="form-control" type="text" id="occupation_type_title" name="occupation_type_title" value="'+occupation_company_name+'"></div>';
                        $('#occupation_type_title_cover').html(str).show();

                        var str = '<div class="form-group"><label for="occupation_type">'+data.occupation_type_lable[1]+'</label><select name="occupation_type" class="form-control">';
                        str += '<option value="">-- Pilih --</option>';
                        if(data.result.length > 0){
                            data.result.forEach(function(item, index){
                                if(item == occupation_company_detail){
                                    selected = "selected";
                                }else{
                                    selected = "";
                                }
                                str += '<option value="'+item+'" '+selected+'>'+item+'</option>';
                            });
                        }
                        str += '</select><div>';
                        $('#occupation_type_cover').html(str).show();
                    }else{
                        $('#occupation_type_title_cover').hide();
                        $('#occupation_type_cover').hide();
                    }
                }

            }
        });
    }
}

function show_occupation_detail(detail){
    var val = detail.occupation

    if(val != ""){
        key = val.toLowerCase();
        key = key.replaceAll(" ", "_");

        $.ajax({
            url : "{{ url('/get-occupation-type') }}/" + key,
            type: "GET",
            dataType: "JSON",
            success: function(data, textStatus, xhr)
            {
                if(xhr.status == '200'){

                    if(data.result.length > 0){
                        var str = '<label for="occupation_type_title">'+data.occupation_type_lable[0]+'</label><div class="detail-value" id="occupation_type_title">'+detail.occupation_company_name+'</div>';
                        $('#occupation_type_title_cover').html(str).show();

                        var str = '<label for="occupation_type">'+data.occupation_type_lable[1]+'</label><div class="detail-value" id="occupation_type_title">'+detail.occupation_company_detail+'</div>';
                        $('#occupation_type_cover').html(str).show();
                    }else{
                        $('#occupation_type_title_cover').hide();
                        $('#occupation_type_cover').hide();
                    }
                }

            }
        });
    }
}

function hide_other(elm_id, is_validation = false){
    if($("#participated"+elm_id).is(":checked")){
        $(".checkboxes").attr("disabled", true).prop('checked', false);
        $("#participated"+elm_id).removeAttr("disabled").prop('checked', true);
    }else{
        if(!is_validation){
            $(".checkboxes").removeAttr("disabled").prop('checked', false);
        }
    }
}
</script>
@endsection
