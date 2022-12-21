@extends('layouts.main')
@section('container')
<div class="d-sm-flex align-items-center justify-content-between">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Artikel</li>
    </ol>
</div>
<div class="row">
    <div class="card col-sm-12">
        <div class="card-header" style="border-bottom: 1px solid rgba(0,0,0,.125) !important;">
            <button type="button" class="btn btn-primary float-right" id="btnSave" onclick="save()">Simpan</button>
        </div>
        <div class="card-body">
            <div id="box_msg_landingpage"></div>
            <form id="form_landingpage" autocomplete="nope">
                <?php echo $html; ?>
            </form>
        </div>
        <div class="card-header" style="border-top: 1px solid rgba(0,0,0,.125) !important;">
            <button type="button" class="btn btn-primary float-right" id="btnSave2" onclick="save()">Simpan</button>
        </div>
    </div>
</div>

<!--Row-->
<!-- Scroll to top -->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<style>
    .note-editable { background-color: #F2F2F2 !important;}
</style>
<!-- Page level custom scripts -->
<script>
var table;

$(document).ready(function() {
    $("#box_msg_landingpage").html('').hide();
    $('#btnSave').text('Simpan');
    $('#btnSave').attr('disabled',false);
    $('#btnSave2').text('Simpan');
    $('#btnSave2').attr('disabled',false);

    $('.textarea').summernote({
        height: 150,
        toolbar: [
            [ 'style', [ 'style' ] ],
            [ 'font', [ 'bold', 'italic', 'underline', 'strikethrough', 'clear'] ],
            [ 'fontname', [ 'fontname' ] ],
            [ 'fontsize', [ 'fontsize' ] ],
            [ 'color', [ 'color' ] ],
            [ 'para', [ 'ol', 'ul', 'paragraph', 'height' ] ],
            [ 'table', [ 'table' ] ],
            [ 'view', [ 'fullscreen', 'codeview' ] ]
        ]
    });

});

function isNumber(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if ( (charCode > 31 && charCode < 48) || charCode > 57) {
        return false;
    }
    return true;
}

function readURL(input) {

    var fileTypes = ['jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF'];

    $('.msg_images').html('');
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        if(input.files[0].size <= 1024000){

            var extension = input.files[0].name.split('.').pop().toLowerCase(),
            isSuccess = fileTypes.indexOf(extension) > -1;

            if(isSuccess){
                reader.onload = function (e) {
                    $('#label_images').hide();
                    $('#show_images').attr('src', e.target.result).fadeOut().fadeIn();
                    $('#file_image_value').val(e.target.result);
                    $('#remove').show();
                };
                reader.readAsDataURL(input.files[0]);
            }else{
                $('#msg_images').html('Format file yang diperbolehkan adalah jpg, JPG, jpeg, JPEG, png, PNG, gif, GIF');
            }
        }else{
            $('#msg_images').html('Maksimal besar file adalah 1024KB');
        }
    }
}

function removeImage()
{
    $('#label_images').show();
    $('#show_images').removeAttr('src').hide();
    $('#file_image_value').val('');
    $('#remove').hide();
    $('.msg_images').html('');
}

function readURLLogo(input) {

    var fileTypes = ['jpg', 'jpeg', 'png', 'gif', 'JPG', 'JPEG', 'PNG', 'GIF'];

    $('.msg_logo').html('');
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        if(input.files[0].size <= 1024000){

            var extension = input.files[0].name.split('.').pop().toLowerCase(),
            isSuccess = fileTypes.indexOf(extension) > -1;

            if(isSuccess){
                reader.onload = function (e) {
                    $('#label_logo').hide();
                    $('#show_logo').attr('src', e.target.result).fadeOut().fadeIn();
                    $('#file_logo_value').val(e.target.result);
                    $('#remove_logo').show();
                };
                reader.readAsDataURL(input.files[0]);
            }else{
                $('#msg_logo').html('Format file yang diperbolehkan adalah jpg, JPG, jpeg, JPEG, png, PNG, gif, GIF');
            }
        }else{
            $('#msg_logo').html('Maksimal besar file adalah 1024KB');
        }
    }
}

function removeLogo()
{
    $('#label_logo').show();
    $('#show_logo').removeAttr('src').hide();
    $('#file_logo_value').val('');
    $('#remove_logo').hide();
    $('.msg_logo').html('');
}

function save()
{
    $('#btnSave').text('Proses...'); //change button text
    $('#btnSave').attr('disabled',true); //set button disable
    $('#btnSave2').text('Proses...'); //change button text
    $('#btnSave2').attr('disabled',true); //set button disable
    var url = "{{ url('landingpage/edit') }}";

    var data_form = $('#form_landingpage').serialize()+ "&" + $.param({_token:"{{ csrf_token() }}"});
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
                    $("#box_msg_landingpage").html('').hide();
                    $("#file_image_value_old").val(data.new_file);
                    await toastr.success(data.message);
                    $('html').animate({ scrollTop: 0 }, 'slow');
                }
                else
                {
                    await $('#box_msg_landingpage').html(data.message).fadeOut().fadeIn();
                    $('#modal_form').animate({ scrollTop: 0 }, 'slow');
                }
            }else{
                toastr.error(xhr.statusText);
            }

            $('#btnSave').text('Simpan');
            $('#btnSave').attr('disabled',false);
            $('#btnSave2').text('Simpan');
            $('#btnSave2').attr('disabled',false);

        }
    });
}

</script>
@endsection
