<nav class="d-print-none navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl z-index-sticky "
  id="navbarBlur" data-scroll="false">
  <div class="container-fluid py-1 px-3 row">
    <div class="col-12 col-sm-6 d-flex">
      @yield('breadcrumb')
      <div class="sidenav-toggler sidenav-toggler-inner d-xl-block d-none mt-3">
        <a href="javascript:;" class="nav-link p-0">
          <div class="sidenav-toggler-inner">
            <i class="sidenav-toggler-line bg-white"></i>
            <i class="sidenav-toggler-line bg-white"></i>
            <i class="sidenav-toggler-line bg-white"></i>
          </div>
        </a>
      </div>
    </div>

    <div class="col-12 col-sm-6">

      <ul class="navbar-nav  justify-content-end">
        <li class="nav-item dropdown pe-2 px-5 mt-1 d-flex align-items-center">
          <a href="javascript:;" class="nav-link text-white p-0" id="dropdownMenuButton" data-bs-toggle="dropdown"
            aria-expanded="false">
            <div class="notification-container" style="position: relative; display: inline-block;">
              <!-- Notification Counter -->
              <div style="background:red; width:13px; height:13px; border-radius:50%; font-size:8px; 
                         display:flex; justify-content:center; align-items:center; position:absolute; 
                         top:-5px; right:-5px; color:white;padding:0;margin:0">
                {{ auth()->user()->unreadNotifications->count()}}
              </div>
              <!-- Bell Icon -->
              <i class="fa fa-bell cursor-pointer" style="font-size:20px"></i>
            </div>

          </a>
          <ul class="dropdown-menu dropdown-menu-end px-3 py-0 me-sm-n4 notifications"
            style="height: 350px; width:30rem;  " aria-labelledby="dropdownMenuButton">

            <div style=" position: relative;" aria-labelledby="dropdownMenuButton">

              <!-- Fixed Header -->
              <div class=" d-flex justify-content-between pt-4 pb-1 align-items-center"
                style="position: sticky; top: 0; background: white; z-index: 10; border-bottom: 1px solid #e0e0e0;font-size:12px;font-weight:700">
                <span>NOTIFICATIONS</span>
                <a href="{{ route('notifications.markAllAsRead') }}" class="text-primary">Mark all as read</a>
              </div>
              @php
        $unreadNotifications = auth()->user()->unreadNotifications()->orderBy('created_at', 'desc')->get();
        $readNotifications = auth()->user()->readNotifications()->orderBy('created_at', 'desc')->get();
        $notifications = $unreadNotifications->merge($readNotifications);
        @endphp

              <!-- Scrollable Notifications List -->
              <ul class="list-group list-group-flush" style="height: 260px;overflow-y:auto">
                @forelse($notifications as $notification)
                @if(isset($notification->data['encrypted_data']))
              @php
        $decryptedData = json_decode(Crypt::decryptString($notification->data['encrypted_data']), true);
        $user = \App\Models\User::find($decryptedData['user_id']);
        @endphp
           <a href="{{ route('toggle.notification', $notification->id) }}"
           style="display:flex;align-items:center">
              <li class="list-group-item d-flex w-100 align-items-center py-3 notification-item hover-effect-custon 
              {{ $notification->read_at ? '' : 'bg-light' }}" style="position: relative; cursor: pointer;">
             
                @if ($notification->read_at)
          <div style="width:5px;height:5px;border-radius:50%;margin-right:15px">
          </div>
        @else
      <div style="width:5px;height:5px;background-color:blue;border-radius:50%;margin-right:15px">
      &nbsp;
      </div>

    @endif

                @if($user && $user->image)
          <img src="{{ asset('images/profile/' . $user->image) }}" alt="avatar" class="rounded-circle me-3"
          width="30" height="30">
        @else
      <i class="fas fa-user-circle fa-2x me-3"></i>
    @endif

                <div>
                <p class="mb-0 py-2 hover-none" style="font-size:12px">
                <strong style="font-weight:700">{{ $user ? $user->name : 'Unknown User' }} </strong>
                {{ $decryptedData['message'] }}
                <span class="text-muted"
                  style="font-size:8px;position:absolute;bottom:2px;right:2px">{{ $notification->created_at->diffForHumans() }}</span>
                </p>
                </div>

              </li>
            </a>
        @endif



        @empty
      <li class="list-group-item text-center" style="height: 260px">
        <p class="mb-0 py-2" style="font-size:14px">No notifications available.</p>
      </li>
    @endforelse
              </ul>

              <!-- Fixed Footer -->
              <div class=" text-center py-2"
                style="position: sticky; bottom: 0; background: white; z-index: 10; border-top: 1px solid #e0e0e0;">
                <a href="#" class="text-primary ">View All</a>
              </div>

            </div>

          </ul>
        </li>


        <li class="nav-item px-4 d-flex align-items-center">

          <a href="javascript:;" class="nav-link text-white p-0">
            <a href="{{route('tickets.assign')}}" class="btn btn-light btn-sm mt-3 shadow-lg px-4 py-3"
              style="border-radius:50px;">Tickets Assign</a>
          </a>
        </li>


        <li class="nav-item  d-flex align-items-center">
          <div class="dropdown">
            <a href="/login" class="nav-link text-white font-weight-bold px-0" target="_blank" data-bs-toggle="dropdown"
              id="navbarDropdownMenuLink2">
              @if (auth()->user()->image != "")
          <img class="relative border-radius-lg avatar-sm rounded-circle" alt="profile"
          src="{{ asset('images/profile/' . auth()->user()->image) }}">
        @else
        <div class="relative rounded-circle bg-white d-flex align-items-center justify-content-center"
        style="width: 40px; height: 40px;">
        <i class="fa fa-user" style="font-size: 20px; color: black;"></i>
        </div>
      @endif
              <span class="d-sm-inline d-none">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink2">
              <li>
                <a class="dropdown-item" href="{{ route('users.profileCreate') }}">Profile Settings</a>
              </li>
              <li>
                <a class="dropdown-item" href="{{ route('logout') }}"
                  onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
              </li>
            </ul>
          </div>
        </li>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
          @csrf
        </form>
        <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
          <a href="javascript:;" class="nav-link text-white p-0" id="iconNavbarSidenav">
            <div class="sidenav-toggler-inner">
              <i class="sidenav-toggler-line bg-white"></i>
              <i class="sidenav-toggler-line bg-white"></i>
              <i class="sidenav-toggler-line bg-white"></i>
            </div>
          </a>
        </li>

      </ul>
    </div>
  </div>
  </div>
</nav>