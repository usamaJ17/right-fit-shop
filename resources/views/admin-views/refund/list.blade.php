@php use Illuminate\Support\Str; @endphp
@extends('layouts.back-end.app')
@section('title',translate('refund_requests'))

@section('content')
    <div class="content container-fluid">

        <div class="mb-3">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img src="{{asset('/public/assets/back-end/img/refund-request.png')}}" alt="">
                {{translate('pending_Refund_Requests')}}
                <span class="badge badge-soft-dark radius-50">{{$refundList->total()}}</span>
            </h2>
        </div>

        <div class="card">
            <div class="p-3">
                <div class="row justify-content-between align-items-center">
                    <div class="col-12 col-md-4">
                        <form action="{{ url()->current() }}" method="GET">
                            <div class="input-group input-group-merge input-group-custom">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="tio-search"></i>
                                    </div>
                                </div>
                                <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                       placeholder="{{translate('search_by_order_id_or_refund_id')}}"
                                       aria-label="Search orders" value="{{ request('searchValue') }}">
                                <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-12 mt-3 col-md-8">

                        <div class="d-flex gap-3 justify-content-md-end">
                            <div class="dropdown text-nowrap">
                                <button type="button" class="btn btn-outline--primary" data-toggle="dropdown">
                                    <i class="tio-download-to"></i>
                                    {{translate('export')}}
                                    <i class="tio-chevron-down"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li>
                                        <a type="submit" class="dropdown-item d-flex align-items-center gap-2 "
                                           href="{{route('admin.refund-section.refund.export',['status'=>request('status'),'searchValue'=>request('searchValue'), 'type'=>request('type')])}}">
                                            <img width="14" src="{{asset('/public/assets/back-end/img/excel.png')}}"
                                                 alt="">
                                            {{translate('excel')}}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <select name="" id="" class="form-control w-auto"
                                    onchange="location.href='{{ url()->current()  }}?type='+this.value">
                                <option
                                    value="all" {{ request('type') == 'all' ?'selected':''}}>{{translate('all')}}</option>
                                <option
                                    value="admin" {{ request('type')== 'admin' ? 'selected':''}}>{{translate('inhouse_Requests')}}</option>
                                <option
                                    value="seller" {{ request('type') == 'seller' ? 'selected':''}}>{{translate('vendor_Requests')}}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>


            <div class="table-responsive datatable-custom">
                <table
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100"
                    style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}}">
                    <thead class="thead-light thead-50 text-capitalize">
                    <tr>
                        <th>{{translate('SL')}}</th>
                        <th>{{translate('order_id')}} </th>
                        <th>{{translate('product_info')}}</th>
                        <th>{{translate('customer_info')}}</th>
                        <th class="text-end">{{translate('total_amount')}}</th>
                        <th>{{translate('refund_status')}}</th>
                        <th class="text-center">{{translate('action')}}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($refundList as $key=>$refund)
                        <tr>
                            <td>{{$refundList->firstItem()+$key}}</td>
                            <td>
                                <a href="{{route('admin.orders.details',['id'=>$refund->order_id])}}"
                                   class="title-color hover-c1">
                                    {{$refund->order_id}}
                                </a>
                            </td>
                            <td>
                                @if ($refund->product !=null)
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{route('admin.products.view',[$refund->product->id])}}">
                                            <img
                                                src="{{ getValidImage(path:'storage/app/public/product/thumbnail/'.$refund->product->thumbnail,type: 'backend-product')}}"
                                                class="avatar border" alt="">
                                        </a>
                                        <div class="d-flex flex-column gap-1">
                                            <a href="{{route('admin.products.view',[$refund->product->id])}}"
                                               class="title-color font-weight-bold hover-c1">
                                                {{Str::limit($refund->product->name,35)}}
                                            </a>
                                            <span
                                                class="fz-12">{{translate('QTY')}} : {{ $refund->orderDetails->qty }}</span>
                                        </div>
                                    </div>
                                @else
                                    {{translate('product_name_not_found')}}
                                @endif

                            </td>
                            <td>
                                @if ($refund->customer !=null)
                                    <div class="d-flex flex-column gap-1">
                                        <a href="{{route('admin.customer.view',[$refund->customer->id])}}"
                                           class="title-color font-weight-bold hover-c1">
                                            {{$refund->customer->f_name. ' '.$refund->customer->l_name}}
                                        </a>
                                        <a href="tel:{{$refund->customer->phone}}"
                                           class="title-color hover-c1 fz-12">{{$refund->customer->phone}}</a>
                                    </div>
                                @else
                                    <a href="javascript:" class="title-color hover-c1">
                                        {{translate('customer_not_found')}}
                                    </a>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1 text-end">
                                    <div>
                                        {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refund->amount), currencyCode: getCurrencyCode())}}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-inline-flex flex-column gap-1">
                                    @if($refund->status=='pending')
                                        <span class="badge badge-soft--primary">{{translate($refund->status)}}</span>
                                    @elseif($refund->status=='approved')
                                        <span class="badge badge-soft-success">{{translate($refund->status)}}</span>
                                    @elseif($refund->status=='rejected')
                                        <span class="badge badge-soft-danger">{{translate($refund->status)}}</span>
                                    @else
                                        <span class="badge badge-soft-warning">{{translate($refund->status)}}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <a class="btn btn-outline--primary btn-sm"
                                       title="{{translate('view')}}"
                                       href="{{route('admin.refund-section.refund.details',['id'=>$refund['id']])}}">
                                        <i class="tio-invisible"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-responsive mt-4">
                <div class="px-4 d-flex justify-content-lg-end">
                    {!! $refundList->links() !!}
                </div>
            </div>

            @if(count($refundList) == 0)
                <div class="text-center p-4">
                    <img class="mb-3 w-160" src="{{asset('public/assets/back-end/svg/illustrations/sorry.svg')}}"
                         alt="Image Description">
                    <p class="mb-0">{{ translate('no_data_to_show')}}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
