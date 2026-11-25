<!DOCTYPE html>
<html lang="en">

<head>
  @include('layouts.partials.header')
  @include('layouts.partials.core_style')
  @yield('style')
  <link href="{{ asset('assets/css/custom.css') }}?v={{ time() }}" rel="stylesheet">
  <link href="{{ asset('assets/css/report.css') }}?v={{ time() }}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.0/dist/css/tom-select.css">
  <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.0/dist/js/tom-select.complete.min.js"></script>


  <!-- <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css"> -->
</head>

<body class="g-sidenav-show bg-white-custom" style="background-color:#F7F7F7">

  {{-- loading indicator --}}
  <!-- <div id="main_loading" class=" bg-primary position-fixed w-100 h-100 d-block " style="z-index: 100; top:0; left:0; text-align: center;opacity: 0.5;z-index:999999">
        <img width="80px" src="{{ asset('website/img/loader.gif') }}" alt="loading"
        style="
        position: absolute;
          top: 36%;
          left: 45%;
          z-index: 100;
        "
        >
        <div class="spinner-grow text-danger"  role="status" style="
        position: absolute;
          top: 37%;
          left: 45%;
          z-index: 100;
          width: 5rem; height: 5rem;
        " >
          <span class="sr-only">Loading...</span>
        </div>
      </div> -->
  {{-- loading indicator end here --}}


  @include('layouts.partials.site_nav')
  <main class="main-content position-relative border-radius-lg">
    <!-- Navbar -->
    @include('layouts.partials.header_nav')
    <!-- End Navbar -->
    {{-- Main contents --}}
    <div class="container-fluid py-4">
      @yield('content')
      @include('layouts.partials.footer')
    </div>
    {{-- @include('layouts.partials.dashboard') --}}
    {{-- main conetns ends here --}}

  </main>
  <!--   Core JS Files   -->
  @include('layouts.partials.core_script')

  @yield('script')

</body>

</html>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

  // hide loadin
  $(window).on('load', function () {
    $('div#main_loading').removeClass('d-block');
    $('div#main_loading').addClass('d-none');
  });
  function toggleNotification(url) {
        const a = document.createElement('a');
        a.href = url;
        document.body.appendChild(a);
        a.click();
        console.log('sdk kskfk')
        document.body.removeChild(a);
    }

</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>