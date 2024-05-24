@php
    use App\Models\Cart;
    use App\Models\CartShipping;
    use App\Models\ShippingType;
    use App\Utils\Helpers;
    use App\Utils\OrderManager;
    use App\Utils\ProductManager;
    use function App\Utils\get_shop_name;
    $shippingMethod = getWebConfig(name: 'shipping_method');
    $cart = Cart::where(['customer_id' => (auth('customer')->check() ? auth('customer')->id() : session('guest_id'))])->with(['seller','allProducts.category'])->get()->groupBy('cart_group_id');
@endphp
<div class="container">
    <h4 class="text-center mb-3 text-capitalize">{{ translate('cart_list') }}</h4>
    <form action="javascript:">
        <div class="row gy-3">
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-center mb-30">
                            <ul class="cart-step-list">
                                <li class="current cursor-pointer get-view-by-onclick"
                                    data-link="{{route('shop-cart')}}">
                                    <span><i class="bi bi-check2"></i></span> {{ translate('cart') }}</li>
                                <li class="cursor-pointer text-capitalize" data-link="{{ route('checkout-details') }}">
                                    <span><i class="bi bi-check2"></i></span> {{ translate('shopping_details') }}</li>
                                <li><span><i class="bi bi-check2"></i></span> {{ translate('payment') }}</li>
                            </ul>
                        </div>
                        @if(count($cart)==0)
                            @php $physical_product = false; @endphp
                        @endif

                        @foreach($cart as $group_key=>$group)
                            @php
                                $physical_product = false;
                                foreach ($group as $row) {
                                    if ($row->product_type == 'physical') {
                                        $physical_product = true;
                                    }
                                }
                            @endphp
                            @foreach($group as $cart_key=>$cartItem)
                                @if ($shippingMethod=='inhouse_shipping')
                                        <?php
                                            $admin_shipping = ShippingType::where('seller_id', 0)->first();
                                            $shipping_type = isset($admin_shipping) === true ? $admin_shipping->shipping_type : 'order_wise';
                                        ?>
                                @else
                                        <?php
                                        if ($cartItem->seller_is == 'admin') {
                                            $admin_shipping = ShippingType::where('seller_id', 0)->first();
                                            $shipping_type = isset($admin_shipping) === true ? $admin_shipping->shipping_type : 'order_wise';
                                        } else {
                                            $seller_shipping = ShippingType::where('seller_id', $cartItem->seller_id)->first();
                                            $shipping_type = isset($seller_shipping) === true ? $seller_shipping->shipping_type : 'order_wise';
                                        }
                                        ?>
                                @endif
                                @if($cart_key==0)
                                    @php
                                        $verify_status = OrderManager::minimum_order_amount_verify($request, $group_key);
                                    @endphp
                                    <div class="bg-primary-light py-2 px-2 px-sm-3 mb-3 mb-sm-4">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center">
                                                @if($cartItem->seller_is=='admin')
                                                    <a href="{{route('shopView',['id'=>0])}}">
                                                        <h5>
                                                            {{getWebConfig(name: 'company_name')}}
                                                        </h5>
                                                    </a>
                                                @else
                                                    <a href="{{route('shopView',['id'=>$cartItem->seller_id])}}">
                                                        <h5>
                                                            {{ get_shop_name($cartItem['seller_id']) }}
                                                        </h5>
                                                    </a>
                                                @endif
                                                @if ($verify_status['minimum_order_amount'] > $verify_status['amount'])
                                                    <span
                                                        class="ps-2 text-danger pulse-button minimum-order-amount-message"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="right"
                                                        data-bs-custom-class="custom-tooltip"
                                                        data-bs-title="{{ translate('minimum_Order_Amount') }} {{ Helpers::currency_converter($verify_status['minimum_order_amount']) }} {{ translate('for') }} @if($cartItem->seller_is=='admin') {{getWebConfig(name: 'company_name')}} @else {{ get_shop_name($cartItem['seller_id']) }} @endif">
                                                    <i class="bi bi-info-circle"></i>
                                                </span>
                                                @endif
                                            </div>
                                            @if($physical_product && $shippingMethod=='sellerwise_shipping' && $shipping_type == 'order_wise')
                                                @php
                                                    $choosen_shipping=CartShipping::where(['cart_group_id'=>$cartItem['cart_group_id']])->first()
                                                @endphp

                                                @if(isset($choosen_shipping)===false)
                                                    @php $choosen_shipping['shipping_method_id']=0 @endphp
                                                @endif
                                                @php
                                                    $shippings=Helpers::get_shipping_methods($cartItem['seller_id'],$cartItem['seller_is'])
                                                @endphp
                                                @if($physical_product && $shippingMethod=='sellerwise_shipping' && $shipping_type == 'order_wise')
                                                    <div class="border bg-white rounded custom-ps-3">
                                                        <div class="shiiping-method-btn d-flex gap-2 p-2 flex-wrap">
                                                            <div
                                                                class="flex-middle flex-nowrap fw-semibold text-dark gap-2">
                                                                <i class="bi bi-truck"></i>
                                                                {{ translate('Shipping_Method') }}:
                                                            </div>
                                                            <div class="dropdown">
                                                                <button type="button" class="border-0 bg-transparent d-flex gap-2 align-items-center dropdown-toggle text-dark p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                                                        <?php
                                                                        $shippings_title = translate('choose_shipping_method');
                                                                        foreach ($shippings as $shipping) {
                                                                            if ($choosen_shipping['shipping_method_id'] == $shipping['id']) {
                                                                                $shippings_title = ucfirst($shipping['title']) . ' ( ' . $shipping['duration'] . ' ) ' . Helpers::currency_converter($shipping['cost']);
                                                                            }
                                                                        }
                                                                        ?>
                                                                    {{ $shippings_title }}
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-left-auto bs-dropdown-min-width--8rem">
                                                                    @foreach($shippings as $shipping)
                                                                        <li class="cursor-pointer set-shipping-id" data-id="{{$shipping['id']}}" data-cart-group="{{$cartItem['cart_group_id']}}">
                                                                            {{$shipping['title'].' ( '.$shipping['duration'].' ) '.Helpers::currency_converter($shipping['cost'])}}
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            <div class="table-responsive d-none d-sm-block">
                                @php
                                    $physical_product = false;
                                    foreach ($group as $row) {
                                        if ($row->product_type == 'physical') {
                                            $physical_product = true;
                                        }
                                    }
                                @endphp
                                <table class="table align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th class="border-0">{{ translate('product_details') }}</th>
                                        <th class="border-0 text-center">{{ translate('qty') }}</th>
                                        <th class="border-0 text-end">{{ translate('unit_price') }}</th>
                                        <th class="border-0 text-end">{{ translate('discount') }}</th>
                                        <th class="border-0 text-end">{{ translate('total') }}</th>
                                        @if ( $shipping_type != 'order_wise')
                                            <th class="border-0 text-end">{{ translate('shipping_cost') }} </th>
                                        @endif
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($group as $cart_key=>$cartItem)
                                        @php($product = $cartItem->allProducts)
                                        <tr>
                                            <td>
                                                <div class="media align-items-center gap-3">
                                                    <div
                                                        class="avatar avatar-xxl rounded border position-relative overflow-hidden">
                                                        <img alt="{{ translate('product') }}"
                                                            src="{{ getValidImage(path: 'storage/app/public/product/thumbnail/'.$cartItem['thumbnail'], type: 'product') }}"
                                                            class="dark-support img-fit rounded img-fluid overflow-hidden {{ $product->status == 0?'blur-section':'' }}">

                                                        @if ($product->status == 0)
                                                            <span class="temporary-closed position-absolute text-center p-2">
                                                                <span class="text-capitalize">{{ translate('not_available') }}</span>
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div
                                                        class="media-body d-flex gap-1 flex-column {{ $product->status == 0?'blur-section':'' }}">
                                                        <h6 class="text-truncate text-capitalize width--20ch" >
                                                            <a href="{{ $product->status == 1?route('product',$cartItem['slug']):'javascript:' }}">{{$cartItem['name']}}</a>
                                                        </h6>
                                                        @foreach(json_decode($cartItem['variations'],true) as $key1 =>$variation)
                                                            <div class="fs-12">{{$key1}} : {{$variation}}</div>
                                                        @endforeach
                                                        <div class="fs-12 text-capitalize">{{ translate('unit_price') }}
                                                            : {{ Helpers::currency_converter($cartItem['price']) }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if ($product->status == 1)
                                                    <div class="quantity quantity--style-two d-inline-flex">
                                                        <span
                                                            class="quantity__minus cart-qty-btn update-cart-quantity-list-cart-data"
                                                            data-min-order="{{ $product->minimum_order_qty }}"
                                                            data-prevent=true
                                                            data-cart="{{ $cartItem['id'] }}" data-value="-1"
                                                            data-action="{{ $cartItem['quantity'] == $product->minimum_order_qty ? 'delete':'minus' }}">
                                                            <i class="{{ $cartItem['quantity'] == ($cartItem?->product?->minimum_order_qty ?? 1) ? 'bi bi-trash3-fill text-danger fs-10' : 'bi bi-dash' }}"></i>
                                                        </span>
                                                        <input type="text"
                                                               class="quantity__qty update-cart-quantity-list-cart-data-input"
                                                               value="{{$cartItem['quantity']}}" name="quantity"
                                                               id="cartQuantityWeb{{$cartItem['id']}}"
                                                               data-min-order="{{ $product->minimum_order_qty }}"
                                                               data-cart="{{ $cartItem['id'] }}" data-value="0"
                                                               data-action=""
                                                               data-min="{{ $cartItem?->product?->minimum_order_qty ?? 1 }}">
                                                        <span
                                                            class="quantity__plus cart-qty-btn update-cart-quantity-list-cart-data"
                                                            data-prevent=true
                                                            data-min-order="{{ $product->minimum_order_qty }}"
                                                            data-cart="{{ $cartItem['id'] }}" data-value="1"
                                                            data-action="">
                                                            <i class="bi bi-plus"></i>
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="quantity quantity--style-two d-inline-flex">
                                                        <span class="quantity__minus cartQuantity{{$cartItem['id']}} update-cart-quantity-list-cart-data"
                                                              data-min-order="{{ $product->minimum_order_qty }}"
                                                              data-prevent=true
                                                              data-cart="{{ $cartItem['id'] }}" data-value="-1"
                                                              data-action="delete"
                                                              data-min="{{$cartItem['quantity']}}">
                                                            <i class="bi bi-trash3-fill text-danger fs-10"></i>
                                                        </span>
                                                        <input type="hidden"
                                                               class="quantity__qty cartQuantity{{ $cartItem['id'] }}"
                                                               value="1" name="quantity[{{ $cartItem['id'] }}]"
                                                               id="cartQuantity{{$cartItem['id']}}"
                                                               data-min="1">
                                                    </div>
                                                @endif

                                            </td>
                                            <td class="text-end">{{ Helpers::currency_converter($cartItem['price']*$cartItem['quantity']) }}</td>
                                            <td class="text-end">{{ Helpers::currency_converter($cartItem['discount']*$cartItem['quantity']) }}</td>
                                            <td class="text-end">{{ Helpers::currency_converter(($cartItem['price']-$cartItem['discount'])*$cartItem['quantity']) }}</td>
                                            <td>
                                                @if ( $shipping_type != 'order_wise')
                                                    {{ Helpers::currency_converter($cartItem['shipping_cost']) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>

                                @php($free_delivery_status = OrderManager::free_delivery_order_amount($group[0]->cart_group_id))

                                @if ($free_delivery_status['status'] && (session()->missing('coupon_type') || session('coupon_type') !='free_delivery'))
                                    <div class="free-delivery-area px-3 mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <img
                                                src="{{ asset('public/assets/front-end/img/icons/free-shipping.png') }}"
                                                alt="{{translate('image')}}" width="40">
                                            @if ($free_delivery_status['amount_need'] <= 0)
                                                <span
                                                    class="text-muted fs-16 text-capitalize">{{ translate('you_get_free_delivery_bonus') }}</span>
                                            @else
                                                <span
                                                    class="need-for-free-delivery font-bold">{{ Helpers::currency_converter($free_delivery_status['amount_need']) }}</span>
                                                <span
                                                    class="text-muted fs-16">{{ translate('add_more_for_free_delivery') }}</span>
                                            @endif
                                        </div>
                                        <div class="progress free-delivery-progress">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: {{ $free_delivery_status['percentage'] .'%'}}"
                                                 aria-valuenow="{{ $free_delivery_status['percentage'] }}"
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="d-flex flex-column d-sm-none">
                                @foreach($group as $cart_key=>$cartItem)
                                    @php($product = $cartItem->allProducts)
                                    <div
                                        class="border-bottom d-flex align-items-start justify-content-between gap-2 py-2">
                                        <div class="media gap-2">
                                            <div
                                                class="avatar avatar-lg rounded border position-relative overflow-hidden">
                                                <img
                                                    src="{{ getValidImage(path: 'storage/app/public/product/thumbnail/'.$cartItem['thumbnail'], type: 'product') }}"
                                                    class="dark-support img-fit rounded img-fluid overflow-hidden {{ $product->status == 0?'blur-section':'' }}"
                                                    alt="">
                                                @if ($product->status == 0)
                                                    <span class="temporary-closed position-absolute text-center p-2">
                                                <span>{{ translate('N/A') }}</span>
                                            </span>
                                                @endif
                                            </div>
                                            <div class="media-body d-flex gap-1 flex-column {{ $product->status == 0?'blur-section':'' }}">
                                                <h6 class="text-truncate text-capitalize width--20ch">
                                                    <a href="{{route('product',$cartItem['slug'])}}">{{$cartItem['name']}}</a>
                                                </h6>
                                                @foreach(json_decode($cartItem['variations'],true) as $key1 =>$variation)
                                                    <div class="fs-12">{{$key1}} : {{$variation}}</div>
                                                @endforeach
                                                <div class="fs-12 text-capitalize">{{ translate('unit_price') }}
                                                    : {{ Helpers::currency_converter($cartItem['price']*$cartItem['quantity']) }}</div>
                                                <div class="fs-12">{{ translate('discount') }}
                                                    : {{ Helpers::currency_converter($cartItem['discount']*$cartItem['quantity']) }}</div>
                                                <div class="fs-12">{{ translate('total') }}
                                                    : {{ Helpers::currency_converter(($cartItem['price']-$cartItem['discount'])*$cartItem['quantity']) }}</div>
                                                @if ( $shipping_type != 'order_wise')
                                                    <div class="fs-12">{{ translate('shipping_cost') }}
                                                        : {{ Helpers::currency_converter($cartItem['shipping_cost']) }}</div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="quantity quantity--style-two flex-column d-inline-flex">
                                            @if ($product->status == 1)
                                                <span class="quantity__minus update-cart-quantity-mobile-list-cart-data"
                                                      data-min-order="{{ $product->minimum_order_qty }}"
                                                      data-prevent=true
                                                      data-cart="{{ $cartItem['id'] }}" data-value="-1"
                                                      data-action="{{ $cartItem['quantity'] == $product->minimum_order_qty ? 'delete':'minus' }}">
                                                    <i class="{{ $cartItem['quantity'] == ($cartItem?->product?->minimum_order_qty ?? 1) ? 'bi bi-trash3-fill text-danger fs-10' : 'bi bi-dash' }}"></i>
                                                </span>
                                                <input type="text"
                                                       class="quantity__qty update-cart-quantity-list-mobile-cart-data-input"
                                                       value="{{$cartItem['quantity']}}" name="quantity"
                                                       id="cartQuantityMobile{{$cartItem['id']}}"
                                                       data-min-order="{{ $product->minimum_order_qty }}"
                                                       data-cart="{{ $cartItem['id'] }}" data-value="0"
                                                       data-action="">
                                                <span class="quantity__plus update-cart-quantity-list-mobile-cart-data"
                                                      data-prevent=true
                                                      data-min-order="{{ $product->minimum_order_qty }}"
                                                      data-cart="{{ $cartItem['id'] }}" data-value="1"
                                                      data-action="">
                                                    <i class="bi bi-plus"></i>
                                                </span>
                                            @else
                                                <span class="quantity__minus update-cart-quantity-list-mobile-cart-data"
                                                      data-prevent=true
                                                      data-min-order="{{ $product->minimum_order_qty }}"
                                                      data-cart="{{ $cartItem['id'] }}" data-value="-1"
                                                      data-action="{{ $cartItem['quantity'] == $product->minimum_order_qty ? 'delete':'minus' }}">
                                                        <i class="bi bi-trash3-fill text-danger fs-10"></i>
                                                </span>
                                                <input type="hidden"
                                                       class="quantity__qty cartQuantity{{ $cartItem['id'] }}"
                                                       data-min-order="{{ $product->minimum_order_qty ?? 1 }}"
                                                       data-cart="{{ $cartItem['id'] }}" data-value="0" data-action=""
                                                       value="{{$cartItem['quantity']}}" name="quantity"
                                                       id="cartQuantityMobile{{$cartItem['id']}}"
                                                       data-min="{{$cartItem['quantity']}}">
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                @php($free_delivery_status = OrderManager::free_delivery_order_amount($group[0]->cart_group_id))

                                @if ($free_delivery_status['status'] && (session()->missing('coupon_type') || session('coupon_type') !='free_delivery'))
                                    <div class="free-delivery-area px-3 mb-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <img
                                                src="{{ asset('public/assets/front-end/img/icons/free-shipping.png') }}"
                                                alt="" width="40">
                                            @if ($free_delivery_status['amount_need'] <= 0)
                                                <span
                                                    class="text-muted fs-16">{{ translate('you_Get_Free_Delivery_Bonus') }}</span>
                                            @else
                                                <span
                                                    class="need-for-free-delivery font-bold">{{ Helpers::currency_converter($free_delivery_status['amount_need']) }}</span>
                                                <span
                                                    class="text-muted fs-16">{{ translate('add_more_for_free_delivery') }}</span>
                                            @endif
                                        </div>
                                        <div class="progress free-delivery-progress">
                                            <div class="progress-bar" role="progressbar"
                                                 style="width: {{ $free_delivery_status['percentage'] .'%'}}"
                                                 aria-valuenow="{{ $free_delivery_status['percentage'] }}"
                                                 aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if($shippingMethod=='inhouse_shipping')
                                <?php
                                $physical_product = false;
                                foreach ($cart as $group_key => $group) {
                                    foreach ($group as $row) {
                                        if ($row->product_type == 'physical') {
                                            $physical_product = true;
                                        }
                                    }
                                }
                                ?>

                                <?php
                                $admin_shipping = ShippingType::where('seller_id', 0)->first();
                                $shipping_type = isset($admin_shipping) === true ? $admin_shipping->shipping_type : 'order_wise';
                                ?>
                            @if ($shipping_type == 'order_wise' && $physical_product)
                                @php($shippings=Helpers::get_shipping_methods(1,'admin'))
                                @php($choosen_shipping=CartShipping::where(['cart_group_id'=>$cartItem['cart_group_id']])->first())

                                @if(isset($choosen_shipping)===false)
                                    @php($choosen_shipping['shipping_method_id']=0)
                                @endif
                                <div class="row">
                                    <div class="col-12">
                                        <select class="form-control text-dark set-shipping-onchange">
                                            <option>{{ translate('choose_shipping_method')}}</option>
                                            @foreach($shippings as $shipping)
                                                <option
                                                    value="{{$shipping['id']}}" {{$choosen_shipping['shipping_method_id']==$shipping['id']?'selected':''}}>
                                                    {{$shipping['title'].' ( '.$shipping['duration'].' ) '.Helpers::currency_converter($shipping['cost'])}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if( $cart->count() == 0)
                            <div class="d-flex justify-content-center align-items-center">
                                <h4 class="text-danger text-capitalize">{{ translate('cart_empty') }}</h4>
                            </div>
                        @endif

                        <form method="get">
                            <div class="form-group mt-3">
                                <div class="row">
                                    <div class="col-12">
                                        <label for="order-note"
                                               class="form-label input-label">{{translate('order_note')}} <span
                                                class="input-label-secondary">({{translate('optional')}})</span></label>
                                        <textarea class="form-control w-100" rows="5" id="order-note"
                                                  name="order_note">{{ session('order_note')}}</textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @include('theme-views.partials._order-summery')
        </div>
    </form>
</div>
@push('script')
    <script src="{{ theme_asset('assets/js/cart.js') }}"></script>
@endpush
