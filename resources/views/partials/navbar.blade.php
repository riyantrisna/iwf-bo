<nav class="navbar navbar-expand navbar-light bg-navbar topbar mb-4 static-top">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
    <i class="fa fa-bars"></i>
    </button>
    <ul class="navbar-nav ml-auto">
    <li class="nav-item dropdown no-arrow">
        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">
			<img class="img-profile rounded-circle" src="{{asset('/assets/img/boy.png')}}" style="max-width: 60px">
			<span class="ml-2 d-none d-lg-inline text-white small">{{ auth()->user()->full_name }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
            <form action="/logout" method="post">
                @csrf
                <button class="dropdown-item" type="submit"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout</button>
            </form>
        </div>
    </li>
    </ul>
</nav>

<script src="{{asset('/assets/vendor/jquery/jquery.min.js')}}"></script>
<script src="{{asset('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('/assets/vendor/jquery-easing/jquery.easing.min.js')}}"></script>
<script src="{{asset('/assets/js/ruang-admin.min.js')}}"></script>
<!-- Page level plugins -->
<script src="{{asset('/assets/vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('/assets/vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
 <!-- Bootstrap Datepicker -->
 <script src="{{asset('/assets/vendor/bootstrap-datepicker/js/bootstrap-datepicker.min.js')}}"></script>
 <!-- Toastr -->
<script src="{{asset('/assets/vendor/toastr/toastr.min.js')}}"></script>
