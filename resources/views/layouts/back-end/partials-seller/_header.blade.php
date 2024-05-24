@php
    use Illuminate\Support\Facades\Session;
    use Illuminate\Support\Str;
    use Illuminate\Support\Carbon;
@endphp
@php($direction = Session::get('direction'))
<div id="headerMain" class="d-none">
    <header id="header"
            class="navbar navbar-expand-lg navbar-fixed navbar-height navbar-flush navbar-container navbar-bordered">
        <div class="navbar-nav-wrap">
            <div class="navbar-brand-wrapper">
                @php($shop=\App\Models\Shop::where(['seller_id'=>auth('seller')->id()])->first())
                <a class="navbar-brand" href="{{route('vendor.dashboard.index')}}" aria-label="">
                    @if (isset($shop))
                        <img class="navbar-brand-logo"
                             src="{{getValidImage('storage/app/public/shop/'.$shop->image,type:'backend-logo')}}" alt="{{translate('logo')}}"
                             height="40">
                        <img class="navbar-brand-logo-mini"
                             src="{{getValidImage('storage/app/public/shop/'.$shop->image,type:'backend-logo')}}"
                             alt="{{translate('logo')}}" height="40">

                    @else
                        <img class="navbar-brand-logo-mini"
                             src="{{asset('public/assets/back-end/img/160x160/img1.jpg')}}"
                             alt="{{translate('logo')}}" height="40">
                    @endif
                </a>
            </div>
            <div class="navbar-nav-wrap-content-left">
                <button type="button" class="js-navbar-vertical-aside-toggle-invoker close mr-3 d-xl-none">
                    <i class="tio-first-page navbar-vertical-aside-toggle-short-align"></i>
                    <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                       data-template='<div class="tooltip d-none d-sm-block" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
                       data-toggle="tooltip" data-placement="right" title="Expand"></i>
                </button>
                <div class="d-none">
                    <form class="position-relative">
                    </form>
                </div>
            </div>
            <div class="navbar-nav-wrap-content-right"
                 style="{{$direction === "rtl" ? 'margin-left:unset; margin-right: auto' : 'margin-right:unset; margin-left: auto'}}">
                <ul class="navbar-nav align-items-center flex-row">

                    <li class="nav-item d-none d-md-inline-block">
                        <div class="hs-unfold">
                            <div>
                                @php( $local = session()->has('local')?session('local'):'en')
                                @php($lang = \App\Models\BusinessSetting::where('type', 'language')->first())
                                <div
                                    class="topbar-text dropdown disable-autohide {{$direction === "rtl" ? 'ml-3' : 'm-1'}} text-capitalize">
                                    <a class="topbar-link dropdown-toggle text-black d-flex align-items-center title-color"
                                       href="javascript:" data-toggle="dropdown"
                                    >
                                        @foreach(json_decode($lang['value'],true) as $data)
                                            @if($data['code']==$local)
                                                <img class="{{$direction === "rtl" ? 'ml-2' : 'mr-2'}}" width="20"
                                                     src="{{asset('public/assets/front-end/img/flags/'.$data['code'].'.png')}}"
                                                     alt="{{$data['name']}}">
                                                {{$data['name']}}
                                            @endif
                                        @endforeach
                                    </a>
                                    <ul class="dropdown-menu">
                                        @foreach(json_decode($lang['value'],true) as $key =>$data)
                                            @if($data['status']==1)
                                                <li class="change-language" data-action="{{route('change-language')}}" data-language-code="{{$data['code']}}">
                                                    <a class="dropdown-item pb-1">
                                                        <img class="{{$direction === "rtl" ? 'ml-2' : 'mr-2'}}"
                                                            width="20"
                                                            src="{{asset('public/assets/front-end/img/flags/'.$data['code'].'.png')}}"
                                                            alt="{{$data['name']}}"/>
                                                        <span class="text-capitalize">{{$data['name']}}</span>
                                                    </a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>

                    <li class="nav-item d-none d-md-inline-block">
                        <div class="hs-unfold">
                            <a title="Website Home"
                               class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary rounded-circle"
                               href="{{route('home')}}" target="_blank">
                                <i class="tio-globe"></i>
                            </a>
                        </div>
                    </li>

                    <li class="nav-item d-none d-md-inline-block">
                        <div class="hs-unfold">
                            <a
                                class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary rounded-circle media align-items-center gap-3 navbar-dropdown-account-wrapper dropdown-toggle-left-arrow dropdown-toggle-empty"
                                href="javascript:"
                                data-hs-unfold-options='{
                                     "target": "#notificationDropdown",
                                     "type": "css-animation"
                                   }'>
                                <i class="tio-notifications-on-outlined"></i>
                                @php($notification=App\Models\Notification::whereBetween('created_at', [auth('seller')->user()->created_at, Carbon::now()])->where('sent_to', 'seller')->whereDoesntHave('notificationSeenBy')->count())
                                @if($notification!=0)
                                    <span
                                        class="btn-status btn-sm-status btn-status-danger notification_data_new_count">{{ $notification }}</span>
                                @endif
                            </a>
                            <div id="notificationDropdown"
                                 class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account py-0 overflow-hidden width--20rem">
                                @php($notification_data=App\Models\Notification::whereBetween('created_at', [auth('seller')->user()->created_at, Carbon::now()])->where('sent_to', 'seller')->with('notificationSeenBy')->latest()->get())
                                @foreach ($notification_data as $item)
                                    <button class="dropdown-item position-relative notification-data-view"
                                            data-id="{{ $item->id }}">
                                    <span class="text-truncate pr-2 d-block"
                                          title="Settings">{{translate($item->title)}}</span>
                                        <span class="fs-10">{{ $item->created_at->diffforHumans() }}</span>
                                        @if($item->notification_seen_by == null)
                                            <span class="badge-soft-danger float-right small py-1 px-2 rounded notification_data_new_badge{{ $item->id }}">{{translate('new')}}</span>
                                        @endif
                                    </button>
                                    <div class="dropdown-divider"></div>
                                @endforeach

                            </div>
                        </div>
                    </li>

                    <li class="nav-item d-none d-md-inline-block">
                        <div class="hs-unfold">
                            <a
                                class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary rounded-circle media align-items-center gap-3 navbar-dropdown-account-wrapper dropdown-toggle dropdown-toggle-left-arrow"
                                href="javascript:"
                                data-hs-unfold-options='{
                                     "target": "#messageDropdown",
                                     "type": "css-animation"
                                   }'
                            >
                                <i class="tio-email"></i>
                                @php($message=\App\Models\Chatting::where(['seen_by_seller'=>0, 'seller_id'=>auth('seller')->id()])->count())
                                @if($message!=0)
                                    <span class="btn-status btn-sm-status btn-status-danger">{{ $message }}</span>
                                @endif
                            </a>
                            <div id="messageDropdown"
                                 class="hs-unfold-content width--16rem dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account">
                                <a class="dropdown-item position-relative"
                                   href="{{route('vendor.messages.index', ['type' => 'customer'])}}">
                                    <span class="text-truncate pr-2"
                                          title="Settings">{{translate('customer')}}</span>
                                    @php($messageCustomer=\App\Models\Chatting::where(['seen_by_seller'=>0, 'seller_id'=>auth('seller')->id()])->whereNotNull(['user_id'])->count())
                                    @if($messageCustomer > 0)
                                        <span
                                            class="btn-status btn-sm-status-custom btn-status-danger">{{$messageCustomer}}</span>
                                    @endif
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item position-relative"
                                   href="{{route('vendor.messages.index', ['type' => 'delivery-man'])}}">
                                    <span class="text-truncate pr-2"
                                          title="Settings">{{translate('delivery_man')}}</span>
                                    @php($messageDeliveryMan =\App\Models\Chatting::where(['seen_by_seller'=>0, 'seller_id'=>auth('seller')->id()])->whereNotNull(['delivery_man_id'])->count())
                                    @if($messageDeliveryMan > 0)
                                        <span class="btn-status btn-sm-status-custom btn-status-danger">{{ $messageDeliveryMan }}</span>
                                    @endif
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item d-none d-md-inline-block">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary rounded-circle"
                               href="{{route('vendor.orders.list',['pending'])}}">
                                <i class="tio-shopping-cart-outlined"></i>
                                @php($order=\App\Models\Order::where(['seller_is'=>'seller','seller_id'=>auth('seller')->id(), 'order_status'=>'pending'])->count())
                                @if($order!=0)
                                    <span class="btn-status btn-sm-status btn-status-danger">{{ $order }}</span>
                                @endif
                            </a>
                        </div>
                    </li>
                    <li class="nav-item view-web-site-info">
                        <div class="hs-unfold">
                            <a href="javascript:"
                               class="bg-white js-hs-unfold-invoker btn btn-icon btn-ghost-secondary rounded-circle open-info-web">
                                <i class="tio-info"></i>
                            </a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker media align-items-center gap-3 navbar-dropdown-account-wrapper dropdown-toggle dropdown-toggle-left-arrow"
                               href="javascript:"
                               data-hs-unfold-options='{
                                     "target": "#accountNavbarDropdown",
                                     "type": "css-animation"
                                   }'>
                                <div class="d-none d-md-block media-body text-right">
                                    <h5 class="profile-name mb-0">{{auth('seller')->user()->name}}</h5>
                                    <span class="fz-12">{{ Str::limit($shop->name, 20) }}</span>
                                </div>
                                <div class="avatar avatar-sm avatar-circle">
                                    <img class="avatar-img"
                                         src="{{getValidImage(path:'storage/app/public/seller/'.auth('seller')->user()->image,type:'backend-profile')}}"
                                         alt="{{translate('image_description')}}">
                                    <span class="avatar-status avatar-sm-status avatar-status-success"></span>
                                </div>
                            </a>
                            <div id="accountNavbarDropdown"
                                 class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account __w-16rem">
                                <div class="dropdown-item-text">
                                    <div class="media align-items-center text-break">
                                        <div class="avatar avatar-sm avatar-circle mr-2">
                                            <img class="avatar-img"
                                                 src="{{getValidImage(path:'storage/app/public/seller/'.auth('seller')->user()->image,type:'backend-profile')}}"
                                                 alt="{{translate('image_description')}}">
                                        </div>
                                        <div class="media-body">
                                            <span class="card-title h5">{{auth('seller')->user()->f_name}}</span>

                                            <span class="card-text">{{auth('seller')->user()->email}}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{route('vendor.profile.update',[auth('seller')->id()])}}">
                                    <span class="text-truncate pr-2" title="Settings">{{translate('settings')}}</span>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item logout" href="javascript:" data-action="{{route('vendor.auth.logout')}}">
                                    <span class="text-truncate pr-2" title="{{translate('sign_out')}}">{{translate('sign_out')}}</span>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div id="website_info" class="bg-secondary w-100 d-none">
            <div class="p-3">
                <div class="bg-white p-1 rounded">
                    <div class="topbar-text dropdown disable-autohide {{$direction === "rtl" ? 'ml-3' : 'm-1'}} text-capitalize">
                        <a class="topbar-link dropdown-toggle title-color d-flex align-items-center" href="#"
                           data-toggle="dropdown">
                            @foreach(json_decode($lang['value'],true) as $data)
                                @if($data['code']==$local)
                                    <img class="{{$direction === "rtl" ? 'ml-2' : 'mr-2'}}"  width="20"
                                         src="{{asset('public/assets/front-end').'/img/flags/'.$data['code']}}.png"
                                         alt="{{$data['name']}}">
                                    {{$data['name']}}
                                @endif
                            @endforeach
                        </a>
                        <ul class="dropdown-menu">
                            @foreach(json_decode($lang['value'],true) as $key =>$data)
                                @if($data['status']==1)
                                    <li class="change-language" data-action="{{route('change-language')}}" data-language-code="{{$data['code']}}">
                                        <a class="dropdown-item pb-1" href="javascript:">
                                            <img class="{{$direction === "rtl" ? 'ml-2' : 'mr-2'}}" width="20"
                                                src="{{asset('public/assets/front-end').'/img/flags/'.$data['code']}}.png"
                                                alt="{{$data['name']}}"/>
                                            <span class="text-capitalize">{{$data['name']}}</span>
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="bg-white p-1 rounded mt-2">
                    <a title="Website home" class="p-2 title-color"
                       href="{{route('home')}}" target="_blank">
                        <i class="tio-globe"></i>
                        {{translate('view_website')}}
                    </a>
                </div>
                <div class="bg-white p-1 rounded mt-2">
                    <a class="p-2  title-color"
                       href="{{route('vendor.messages.index', ['type' => 'customer'])}}">
                        <i class="tio-email"></i>
                        {{translate('message')}}
                        @php($message=\App\Models\Chatting::where(['seen_by_seller'=>1,'seller_id'=>auth('seller')->id()])->count())
                        @if($message!=0)
                            <span>({{ $message }})</span>
                        @endif
                    </a>
                </div>
                <div class="bg-white p-1 rounded mt-2">
                    <a class="p-2 title-color"
                       href="{{route('vendor.orders.list',['pending'])}}">
                        <i class="tio-shopping-cart-outlined"></i>
                        {{translate('order_list')}}
                    </a>
                </div>
            </div>
        </div>
    </header>
</div>
<div id="headerFluid" class="d-none"></div>
<div id="headerDouble" class="d-none"></div>
