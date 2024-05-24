@php use Illuminate\Support\Str; @endphp
@extends('layouts.back-end.app-seller')

@section('title', translate('refund_list'))

@section('content')
    <div class="content container-fluid">
        <div class="">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div class="">
                    <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                        <img width="20" src="{{asset('/public/assets/back-end/img/refund-request-list.png')}}" alt="">
                        {{translate('refund_request_list')}}
                        <span class="badge badge-soft-dark radius-50">{{$refundList->total()}}</span>
                    </h2>
                </div>
                <div>
                    <i class="tio-shopping-cart title-color fz-30"></i>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="flex-between justify-content-between align-items-center flex-grow-1">
                    <div class="col-12 col-md-4">
                        <form action="{{ url()->current() }}" method="GET">
                            <div class="input-group input-group-merge input-group-custom">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="tio-search"></i>
                                    </div>
                                </div>
                                <input id="datatableSearch_" type="search" name="search" class="form-control"
                                       placeholder="{{translate('search_by_order_id_or_refund_id')}}"
                                       aria-label="Search orders" value="{{ $searchValue }}"
                                       required>
                                <button type="submit" class="btn btn--primary">{{translate('search')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="table-responsive datatable-custom">
                <table
                    class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table text-start">
                    <thead class="thead-light thead-50 text-capitalize">
                    <tr>
                        <th>{{translate('SL')}}</th>
                        <th>{{translate('order_ID')}} </th>
                        <th>{{translate('product_Info')}}</th>
                        <th>{{translate('customer_Info')}}</th>
                        <th>{{translate('total_Amount')}}</th>
                        <th>{{translate('order_Status')}}</th>
                        <th class="text-center">{{translate('action')}}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($refundList as $key=>$refund)
                        <tr>
                            <td>
                                {{$refundList->firstItem()+$key}}
                            </td>
                            <td>
                                <a class="title-color hover-c1"
                                   href="{{route('vendor.orders.details',[$refund->order_id])}}">
                                    {{$refund->order_id}}
                                </a>
                            </td>
                            <td>
                                @if ($refund->product!=null)
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{route('vendor.products.view',[$refund->product->id])}}">
                                            <img src="{{getValidImage(path:'storage/app/public/product/thumbnail/'.$refund->product->thumbnail ,type:'backend-product')}}"
                                                 class="avatar border" alt="">
                                        </a>
                                        <div class="d-flex flex-column gap-1">
                                            <a href="{{route('vendor.products.view',[$refund->product->id])}}"
                                               class="title-color font-weight-bold hover-c1">
                                                {{Str::limit($refund->product->name,35)}}
                                            </a>
                                            <span class="fz-12">{{ translate('qty') }} : {{ $refund->orderDetails->qty }}</span>
                                        </div>
                                    </div>
                                @else
                                    {{translate('product_name_not_found')}}
                                @endif
                            </td>
                            <td>
                                @if ($refund->customer !=null)
                                    <div class="d-flex flex-column gap-1">
                                        <a href="javascript:void(0)" class="title-color font-weight-bold hover-c1">
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
                                {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $refund->amount), currencyCode: getCurrencyCode())}}
                            </td>
                            <td>
                                {{translate($refund->status)}}
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <a class="btn btn--primary btn-sm square-btn"
                                       title="{{translate('view')}}"
                                       href="{{route('vendor.refund.details',['id'=>$refund['id']])}}">
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
            @if(count($refundList)==0)
                <div class="text-center p-4">
                    <img class="mb-3 __w-7rem" src="{{asset('public/assets/back-end/svg/illustrations/sorry.svg')}}"
                         alt="{{translate('image_description')}}">
                    <p class="mb-0">{{ translate('no_data_to_show')}}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
