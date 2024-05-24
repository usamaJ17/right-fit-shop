@extends('layouts.front-end.app')

@section('title', translate('order_Details'))

@section('content')

    <div class="container pb-5 mb-2 mb-md-4 mt-3 rtl __inline-47 text-align-direction">
        <div class="row g-3">
            @include('web-views.partials._profile-aside')

            <section class="col-lg-9">
                @include('web-views.users-profile.account-details.partial')
                <div class="bg-white border-lg rounded mobile-full">
                    <div class="p-lg-3 p-0">
                        <div class="d-flex justify-content-between gap-2 flex-wrap mb-3">
                            <div class="d-flex gap-3 flex-wrap">
                                @if($order->order_type == 'default_type' && getWebConfig(name: 'order_verification'))
                                    <div class="bg-light rounded py-2 px-3 d-flex align-items-center">
                                        <div class="fs-14 text-capitalize">
                                            {{translate('order_verification_code')}} : <strong
                                                class="text-base">{{$order['verification_code']}}</strong>
                                        </div>
                                    </div>
                                @endif
                                @if($order->order_type == 'POS')
                                    <span
                                        class="pos-btn bg-light hover-none border border-primary-light">{{translate('POS_Order')}}</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-start gap-2">
                                <button type="button" class="btn btn-square d-none d-md-block get-view-by-onclick"
                                        data-link="{{route('generate-invoice',[$order->id])}}">
                                    <img src="{{asset('public/assets/front-end/img/icons/downloads.png')}}" alt="">
                                </button>
                                @if($order->order_status=='delivered')
                                    <button
                                        class="btn btn--primary btn-sm h-40px rounded text_capitalize d-none d-md-block get-order-again-function"
                                        data-id="{{ $order->id }}">
                                        {{ translate('reorder') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="card border-sm">
                            <div class="p-lg-3">
                                <div class="border-lg rounded payment mb-lg-3 table-responsive">
                                    <table class="table table-borderless mb-0">
                                        <thead>
                                        <tr class="order_table_tr">
                                            <td class="order_table_td">
                                                <div class="">
                                                    <div
                                                        class="_1 py-2 d-flex justify-content-between align-items-center">
                                                        <h6 class="font-weight-bold text-capitalize">{{translate('payment_info')}}</h6>
                                                        <button type="button" class="btn btn-square d-sm-none get-view-by-onclick"
                                                                data-link="{{route('generate-invoice',[$order->id])}}">
                                                            <img src="{{asset('public/assets/front-end/img/icons/downloads.png')}}" alt="">
                                                        </button>
                                                    </div>
                                                    <div class="fs-12">
                                                        <span
                                                            class="text-muted text-capitalize">{{translate('payment_status')}}</span>
                                                        :
                                                        <span
                                                            class="text-{{$order['payment_status'] == 'paid' ? 'success' : 'danger'}} text-capitalize">{{$order['payment_status']}}</span>
                                                    </div>
                                                    <div class="mt-2 fs-12">
                                                        <span
                                                            class="text-muted text-capitalize">{{translate('payment_method')}}</span>
                                                        :
                                                        <span
                                                            class="text-primary text-capitalize">{{translate($order['payment_method'])}}</span>
                                                    </div>
                                                    @if($order->payment_method == 'offline_payment' && isset($order->offlinePayments))
                                                        <button type="button"
                                                                class="btn bg-light border border-primary-light mt-3 rounded-pill btn-sm text-capitalize"
                                                                data-toggle="modal"
                                                                data-target="#verifyViewModal">{{translate('see_payment_details')}}</button>
                                                    @endif
                                                </div>
                                            </td>
                                            @if( $order->order_type == 'default_type')

                                                @php($shippingAddressShow = 0)
                                                @foreach($order->details as $details)
                                                    @if(isset($details->product_details) && json_decode($details->product_details)->product_type == "physical")
                                                        @php($shippingAddressShow = 1)
                                                    @endif
                                                @endforeach

                                                @php($shipping=$order['shipping_address_data'])
                                                @if($shipping && $shippingAddressShow)
                                                    <td class="order_table_td">
                                                        <div class="">
                                                            <div class=" py-2">
                                                                <h6 class="font-weight-bold text-capitalize">{{translate('shipping_address')}}
                                                                    : </h6>
                                                            </div>
                                                            <div class="">
                                                                <span class="text-capitalize fs-12">
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px">{{translate('name')}}</span> :&nbsp; {{$shipping->contact_person_name}}
                                                                    </span>
                                                                    <br>
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px">{{translate('phone')}}</span> :&nbsp; {{$shipping->phone}},
                                                                    </span>
                                                                    <br>
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px"> {{translate('address')}}</span> :&nbsp;
                                                                        {{$shipping->address}},
                                                                        {{$shipping->city}}
                                                                        , {{$shipping->zip}}
                                                                    </span>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                @endif
                                                <td class="order_table_td">
                                                    <div class="">
                                                        <div class=" py-2">
                                                            <h6 class="font-weight-bold text-capitalize">{{translate('billing_address')}}
                                                                : </h6>
                                                        </div>
                                                        <div class="">
                                                            @php($billing=$order['billing_address_data'])
                                                            <span class="text-capitalize fs-12">
                                                                @if($billing)
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px">{{translate('name')}}</span> : &nbsp;{{$billing->contact_person_name}}
                                                                    </span>
                                                                    <br>
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px">{{translate('phone')}}</span> : &nbsp;{{$billing->phone}},
                                                                    </span>
                                                                    <br>
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px"> {{translate('address')}}</span> :&nbsp;
                                                                        {{$billing->address}},
                                                                        {{$billing->city}}
                                                                        , {{$billing->zip}}
                                                                    </span>
                                                                @else
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px">{{translate('name')}}</span> : &nbsp;{{$shipping->contact_person_name}}
                                                                    </span>
                                                                    <br>
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px">{{translate('phone')}}</span> :&nbsp; {{$shipping->phone}},
                                                                    </span>
                                                                    <br>
                                                                    <span class="text-capitalize">
                                                                        <span
                                                                            class="min-w-60px"> {{translate('address')}}</span> :&nbsp;
                                                                        {{$shipping->address}},
                                                                        {{$shipping->city}}
                                                                        , {{$shipping->zip}}
                                                                    </span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                        </thead>
                                    </table>
                                </div>

                                <div class="payment mb-3 table-responsive d-none d-lg-block">
                                    <table class="table table-borderless min-width-600px">
                                        <thead class="thead-light text-capitalize">
                                        <tr>
                                            <th>{{translate('order_details')}}</th>
                                            <th>{{translate('qty')}}</th>
                                            <th class="text-right">{{translate('price')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($order->details as $key=>$detail)
                                            @php($product=json_decode($detail->product_details,true))
                                            @if($product)
                                                <tr>
                                                    <td class="for-tab-img">
                                                        <div class="media gap-3 align-items-center">
                                                            <div class="position-relative">
                                                                @if($product['discount'] > 0)
                                                                    <span class="for-discount-value px-1">
                                                                            @if ($product['discount_type'] == 'percent')
                                                                            {{round($product['discount'],(!empty($decimal_point_settings) ? $decimal_point_settings: 0))}}
                                                                            %
                                                                        @elseif($product['discount_type'] =='flat')
                                                                            {{ webCurrencyConverter(amount: $product['discount']) }}
                                                                        @endif {{translate('off')}}
                                                                        </span>
                                                                @endif

                                                                @if($detail->product_all_status)
                                                                    <img class="d-block get-view-by-onclick"
                                                                         data-link="{{ route('product',$product['slug']) }}"
                                                                         src="{{ getValidImage(path: 'storage/app/public/product/thumbnail/'.$detail->product_all_status['thumbnail'], type: 'product') }}"
                                                                         alt="{{ translate('product') }}" width="100">
                                                                @else
                                                                    <img class="d-block get-view-by-onclick"
                                                                         data-link="{{ route('product',$product['slug']) }}"
                                                                         src="{{ getValidImage(path: 'storage/app/public/product/thumbnail/'.$product['thumbnail'], type: 'product') }}"
                                                                         alt="{{ translate('product') }}" width="100">
                                                                @endif
                                                            </div>

                                                            <div class="media-body">
                                                                <a href="{{route('product',[$product['slug']])}}">
                                                                    {{isset($product['name']) ? Str::limit($product['name'],40) : ''}}
                                                                </a>
                                                                @if($detail->refund_request == 1)
                                                                    <small> ({{translate('refund_pending')}}) </small>
                                                                    <br>
                                                                @elseif($detail->refund_request == 2)
                                                                    <small> ({{translate('refund_approved')}}) </small>
                                                                    <br>
                                                                @elseif($detail->refund_request == 3)
                                                                    <small> ({{translate('refund_rejected')}}) </small>
                                                                    <br>
                                                                @elseif($detail->refund_request == 4)
                                                                    <small> ({{translate('refund_refunded')}}) </small>
                                                                    <br>
                                                                @endif<br>
                                                                @if($detail->variant)
                                                                    <small>
                                                                        <span>{{translate('variant')}} : </span>
                                                                        {{$detail->variant}}
                                                                    </small>
                                                                @endif

                                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                                    @if($detail->product && $order->payment_status == 'paid' && $detail->product->digital_product_type == 'ready_product')
                                                                        <a class="btn btn-sm rounded btn--primary action-digital-product-download"
                                                                           data-link="{{ route('digital-product-download', $detail->id) }}"
                                                                           href="javascript:">{{translate('download')}}
                                                                            <i class="tio-download-from-cloud"></i></a>
                                                                    @elseif($detail->product && $order->payment_status == 'paid' && $detail->product->digital_product_type == 'ready_after_sell')
                                                                        @if($detail->digital_file_after_sell)
                                                                            <a class="btn btn-sm rounded btn--primary action-digital-product-download"
                                                                               data-link="{{ route('digital-product-download', $detail->id) }}"
                                                                               href="javascript:">{{translate('download')}}
                                                                                <i class="tio-download-from-cloud"></i></a>
                                                                        @else

                                                                            <span class="" data-toggle="tooltip"
                                                                                  data-placement="top"
                                                                                  title="{{translate('product_not_uploaded_yet')}}">
                                                                                    <a class="btn btn-sm rounded btn--primary disabled">{{translate('download')}} <i
                                                                                            class="tio-download-from-cloud"></i></a>
                                                                                </span>
                                                                        @endif
                                                                    @endif
                                                                        <?php
                                                                        $refund_day_limit = getWebConfig(name: 'refund_day_limit');
                                                                        $order_details_date = $detail->created_at;
                                                                        $current = \Carbon\Carbon::now();
                                                                        $length = $order_details_date->diffInDays($current);
                                                                        ?>

                                                                    @if($order->order_type == 'default_type')
                                                                        @if($order->order_status=='delivered')
                                                                            @if (isset($detail->product))
                                                                                <button type="button"
                                                                                        class="btn btn-sm rounded btn-warning"
                                                                                        data-toggle="modal"
                                                                                        data-target="#submitReviewModal{{$detail->id}}">
                                                                                    @if (isset($detail->product->reviewsByCustomer[0]))
                                                                                        <i class="tio-star-half"></i>{{translate('Update_Review')}}
                                                                                    @else
                                                                                        <i class="tio-star-half"></i>{{translate('review')}}
                                                                                    @endif
                                                                                </button>
                                                                            @endif

                                                                            @if($detail->refund_request !=0)
                                                                                <button type="button"
                                                                                        class="btn btn-sm rounded btn--primary action-get-refund-details"
                                                                                        data-route="{{ route('refund-details', ['id'=>$detail->id]) }}">
                                                                                        {{translate('refund_details')}}
                                                                                    </button>
                                                                            @endif
                                                                            @if( $length <= $refund_day_limit && $detail->refund_request == 0)
                                                                                <button
                                                                                    class="btn btn-sm rounded btn--primary"
                                                                                    data-toggle="modal"
                                                                                    data-target="#refundModal{{$detail->id}}">{{translate('refund')}}</button>
                                                                            @endif
                                                                        @endif
                                                                    @endif

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="pl-2">
                                                            <span
                                                                class="word-nobreak font-weight-bold">{{$detail->qty}}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-right">
                                                        <span
                                                            class="font-weight-bold amount">{{ webCurrencyConverter(amount: $detail->price) }} </span>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 d-flex flex-column gap-2 d-lg-none">
                            <hr>
                            @foreach ($order->details as $key=>$detail)
                                @php($product=json_decode($detail->product_details,true))
                                @if($product)
                                    <div class="bg-white border rounded p-3">
                                        <div class="d-flex justify-content-between gap-3">
                                            <div class="for-tab-img">
                                                <div class="media flex-wrap gap-2">
                                                    <div class="position-relative">
                                                        @if($product['discount'] > 0)
                                                            <span class="for-discount-value px-1">
                                                                @if ($product['discount_type'] == 'percent')
                                                                    {{round($product['discount'],(!empty($decimal_point_settings) ? $decimal_point_settings: 0))}}
                                                                    %
                                                                @elseif($product['discount_type'] =='flat')
                                                                    {{ webCurrencyConverter(amount: $product['discount']) }}
                                                                @endif {{translate('off')}}
                                                            </span>
                                                        @endif
                                                        <img class="d-block get-view-by-onclick"
                                                             data-link="{{ route('product',$product['slug']) }}"
                                                             src="{{ getValidImage(path: 'storage/app/public/product/thumbnail/'.$product['thumbnail'], type: 'product') }}"
                                                             alt="{{ translate('product') }}" width="80">
                                                    </div>

                                                    <div class="media-body">
                                                        <a href="{{route('product',[$product['slug']])}}" class="fs-14">
                                                            {{isset($product['name']) ? Str::limit($product['name'],40) : ''}}
                                                        </a>
                                                        @if($detail->refund_request == 1)
                                                            <small> ({{translate('refund_pending')}}) </small>
                                                        @elseif($detail->refund_request == 2)
                                                            <small> ({{translate('refund_approved')}}) </small>
                                                        @elseif($detail->refund_request == 3)
                                                            <small> ({{translate('refund_rejected')}}) </small>
                                                        @elseif($detail->refund_request == 4)
                                                            <small> ({{translate('refund_refunded')}}) </small>
                                                        @endif<br>
                                                        <span class="d-flex justify-content-between">
                                                            @if($detail->variant)
                                                                <small>
                                                                    <span
                                                                        class="text-muted">{{translate('variant')}} : </span>
                                                                    {{$detail->variant}}
                                                                </small>
                                                            @endif
                                                            <small>
                                                                <span class="text-muted">{{translate('qty')}} : </span>
                                                                {{$detail->qty}}
                                                            </small>
                                                        </span>

                                                        <small class="d-flex align-items-center gap-2">
                                                            <span class="text-nowrap text-muted">
                                                                {{translate('price')}} :
                                                            </span>
                                                            <span class="font-weight-bold amount">{{ webCurrencyConverter(amount: $detail->price) }}</span>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end flex-wrap gap-2 mt-2">
                                            @if($detail->product && $order->payment_status == 'paid' && $detail->product->digital_product_type == 'ready_product')
                                                <a class="btn btn-sm rounded btn--primary action-digital-product-download"
                                                   data-link="{{ route('digital-product-download', $detail->id) }}"
                                                   href="javascript:">{{translate('download')}} <i
                                                        class="tio-download-from-cloud"></i></a>
                                            @elseif($detail->product && $order->payment_status == 'paid' && $detail->product->digital_product_type == 'ready_after_sell')
                                                @if($detail->digital_file_after_sell)
                                                    <a class="btn btn-sm rounded btn--primary action-digital-product-download"
                                                       data-link="{{ route('digital-product-download', $detail->id) }}"
                                                       href="javascript:">{{translate('download')}} <i
                                                            class="tio-download-from-cloud"></i></a>
                                                @else

                                                    <span class="" data-toggle="tooltip" data-placement="top"
                                                          title="{{translate('product_not_uploaded_yet')}}">
                                                        <a class="btn btn-sm rounded btn--primary disabled">{{translate('download')}} <i
                                                                class="tio-download-from-cloud"></i></a>
                                                    </span>
                                                @endif
                                            @endif
                                                <?php
                                                $refund_day_limit = getWebConfig(name: 'refund_day_limit');
                                                $order_details_date = $detail->created_at;
                                                $current = \Carbon\Carbon::now();
                                                $length = $order_details_date->diffInDays($current);
                                                ?>

                                            @if($order->order_type == 'default_type')
                                                @if($order->order_status=='delivered')
                                                    @if (isset($detail->product))
                                                        <button type="button" class="btn btn-sm rounded btn-warning"
                                                                data-toggle="modal"
                                                                data-target="#submitReviewModal{{$detail->id}}">
                                                            @if (isset($detail->product->reviewsByCustomer[0]))
                                                                <i class="tio-star-half"></i>{{translate('Update_Review')}}
                                                            @else
                                                                <i class="tio-star-half"></i>{{translate('review')}}
                                                            @endif
                                                        </button>
                                                    @endif
                                                    @if($detail->refund_request !=0)
                                                        <button type="button" class="btn btn-sm rounded btn--primary action-get-refund-details"
                                                                data-route="{{ route('refund-details',['id'=>$detail->id]) }}">
                                                                {{translate('refund_details')}}
                                                        </button>
                                                    @endif
                                                    @if( $length <= $refund_day_limit && $detail->refund_request == 0)
                                                        <button class="btn btn-sm rounded btn--primary"
                                                            data-toggle="modal" data-target="#refundModal{{$detail->id}}">
                                                            {{ translate('refund') }}
                                                        </button>
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                            <hr>
                        </div>

                        @php($summary = getOrderSummary(order: $order))
                        <?php
                        if ($order['extra_discount_type'] == 'percent') {
                            $extra_discount = ($summary['subtotal'] / 100) * $order['extra_discount'];
                        } else {
                            $extra_discount = $order['extra_discount'];
                        }
                        ?>
                        <div class="row d-flex justify-content-end mt-2">
                            <div class="col-md-8 col-lg-5">
                                <div class="bg-white border-sm rounded">
                                    <div class="card-body ">
                                        <table class="calculation-table table table-borderless mb-0">
                                            <tbody class="totals">
                                            <tr>
                                                <td>
                                                    <div class="text-start">
                                                        <span class="product-qty ">{{translate('item')}}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        class="text-end">
                                                        <span>{{$order->total_qty}}</span>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    <div
                                                        class="text-start"><span
                                                            class="product-qty ">{{translate('subtotal')}}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        class="text-end">
                                                        <span>{{ webCurrencyConverter(amount: $summary['subtotal']) }}</span>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    <div
                                                        class="text-start"><span
                                                            class="product-qty ">{{translate('tax_fee')}}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        class="text-end">
                                                        <span>{{ webCurrencyConverter(amount: $summary['total_tax']) }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @if($order->order_type == 'default_type')
                                                <tr>
                                                    <td>
                                                        <div
                                                            class="text-start"><span
                                                                class="product-qty ">{{translate('shipping_Fee')}}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div
                                                            class="text-end">
                                                            <span>{{ webCurrencyConverter(amount: $summary['total_shipping_cost'] - ($order['is_shipping_free'] ? $order['extra_discount'] : 0)) }}</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif

                                            <tr>
                                                <td>
                                                    <div
                                                        class="text-start"><span
                                                            class="product-qty ">{{translate('discount_on_product')}}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        class="text-end">
                                                        <span>- {{ webCurrencyConverter(amount: $summary['total_discount_on_product']) }}</span>
                                                    </div>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>
                                                    <div
                                                        class="text-start"><span
                                                            class="product-qty ">{{translate('coupon_discount')}}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        class="text-end">
                                                        <span>- {{ webCurrencyConverter(amount: $order->discount_amount) }}</span>
                                                    </div>
                                                </td>
                                            </tr>

                                            @if($order->order_type != 'default_type')
                                                <tr>
                                                    <td>
                                                        <div
                                                            class="text-start"><span
                                                                class="product-qty ">{{translate('extra_discount')}}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div
                                                            class="text-end">
                                                            <span>- {{ webCurrencyConverter(amount: $extra_discount) }}</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif

                                            <tr class="border-top">
                                                <td>
                                                    <div
                                                        class="text-start"><span
                                                            class="font-weight-bold"><strong>{{translate('total')}}</strong></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div
                                                        class="text-end">
                                                        <span
                                                            class="font-weight-bold amount ">{{ webCurrencyConverter(amount: $order->order_amount) }}</span>
                                                    </div>
                                                </td>
                                            </tr>

                                            </tbody>
                                        </table>
                                        @if ($order['order_status']=='pending')
                                            <button class="btn btn-block bg-soft-danger text-danger mt-3 call-route-alert"
                                                data-route="{{ route('order-cancel',[$order->id]) }}"
                                                data-message="{{translate('want_to_cancel_this_order?')}}">
                                                {{translate('cancel_order')}}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    @foreach ($order->details as $key=>$detail)
        @php($product=json_decode($detail->product_details,true))
        @if($product)
            @include('layouts.front-end.partials.modal._review',['id'=>$detail->id,'order_details'=>$detail])
            @include('layouts.front-end.partials.modal._refund',['id'=>$detail->id,'order_details'=>$detail,'order'=>$order,'product'=>$product])
        @endif
    @endforeach

    @if($order->order_status=='delivered')
        <div class="bottom-sticky_offset"></div>
        <div class="bottom-sticky_ele bg-white d-md-none p-3 ">
            <button class="btn btn--primary w-100 text_capitalize get-order-again-function" data-id="{{ $order->id }}">
                {{ translate('reorder') }}
            </button>
        </div>
    @endif

    @if($order->payment_method == 'offline_payment' && isset($order->offlinePayments))
        <div class="modal fade" id="verifyViewModal" tabindex="-1" aria-labelledby="verifyViewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content rtl">
                    <div class="modal-header d-flex justify-content-end  border-0 pb-0">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>

                    <div class="modal-body pt-0">
                        <h5 class="mb-3 text-center text-capitalize">{{translate('payment_verification')}}</h5>

                        <div class="shadow-sm rounded p-3">
                            <h6 class="mb-3 text-capitalize">{{translate('customer_information')}}</h6>

                            <div class="d-flex flex-column gap-2 fs-12">
                                <div class="d-flex align-items-center gap-2">
                                    <span>{{translate('name')}}</span>:
                                    <span class="text-dark"> <a class="text-body text-capitalize" href="Javascript:"> {{$order->customer->f_name ?? transalte('name_not_found')}}&nbsp;{{$order->customer->l_name ?? ''}}  </a>  </span>
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                    <span>{{translate('phone')}}</span>:
                                    <span
                                        class="text-dark">{{$order->customer->phone ?? translate('number_not_found')}}</span>
                                </div>
                            </div>

                            <div class="mt-5">
                                <h6 class="mb-3 text-capitalize">{{translate('payment_information')}}</h6>

                                <div class="d-flex flex-column gap-2 fs-12">

                                    @foreach ($order->offlinePayments->payment_info as $key=>$value)
                                        @if ($key != 'method_id')
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-capitalize">{{translate($key)}}</span>:
                                                <span class="text-dark"> <a class="text-body text-capitalize"
                                                                            href="#"> {{$value}}  </a>  </span>
                                            </div>
                                        @endif
                                    @endforeach

                                    @if($order->payment_note)
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-capitalize">{{ translate('payment_none') }}</span>:
                                        <span class="text-dark"> {{ $order->payment_note }}  </span>
                                    </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="refund_details_modal" tabindex="-1" aria-labelledby="refundRequestModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h6 class="text-center text-capitalize m-0 flex-grow-1">{{translate('refund_details')}}</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body d-flex flex-column gap-3" id="refund_details_field">

                </div>
            </div>
        </div>
    </div>
@endsection


@push('script')
    <script src="{{ asset('public/assets/front-end/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{ asset('public/assets/front-end/js/account-order-details.js') }}"></script>
@endpush
