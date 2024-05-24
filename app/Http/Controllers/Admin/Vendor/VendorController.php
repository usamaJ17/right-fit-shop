<?php

namespace App\Http\Controllers\Admin\Vendor;

use App\Contracts\Repositories\DeliveryManRepositoryInterface;
use App\Contracts\Repositories\DeliveryZipCodeRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\OrderTransactionRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Contracts\Repositories\ShippingAddressRepositoryInterface;
use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Contracts\Repositories\VendorWalletRepositoryInterface;
use App\Contracts\Repositories\WithdrawRequestRepositoryInterface;
use App\Enums\ExportFileNames\Admin\Vendor as VendorExport;
use App\Enums\ViewPaths\Admin\Vendor;
use App\Enums\WebConfigKey;
use App\Events\WithdrawStatusUpdateEvent;
use App\Exports\SellerListExport;
use App\Exports\SellerWithdrawRequest;
use App\Http\Controllers\BaseController;
use App\Traits\CommonTrait;
use App\Traits\PaginatorTrait;
use App\Traits\PushNotificationTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VendorController extends BaseController
{
    use PaginatorTrait;
    use CommonTrait;
    use PushNotificationTrait;

    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepo,
        private readonly OrderRepositoryInterface $orderRepo,
        private readonly ProductRepositoryInterface $productRepo,
        private readonly ReviewRepositoryInterface $reviewRepo,
        private readonly DeliveryManRepositoryInterface $deliveryManRepo,
        private readonly OrderTransactionRepositoryInterface $orderTransactionRepo,
        private readonly ShippingAddressRepositoryInterface $shippingAddressRepo,
        private readonly DeliveryZipCodeRepositoryInterface $deliveryZipCodeRepo,
        private readonly WithdrawRequestRepositoryInterface $withdrawRequestRepo,
        private readonly VendorWalletRepositoryInterface $vendorWalletRepo,
    )
    {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View
     * Index function is the starting point of a controller
     */
    public function index(Request|null $request, string $type = null): View
    {
        return $this->getListView($request);
    }

    public function getListView(Request $request): View
    {
        $current_date = date('Y-m-d');
        $sellers = $this->vendorRepo->getListWhere(
            orderBy:['id'=>'desc'],
            searchValue: $request['searchValue'],
            relations: ['orders', 'product'],
            dataLimit:getWebConfig(name: WebConfigKey::PAGINATION_LIMIT)
        );
        return view(Vendor::LIST[VIEW], compact('sellers', 'current_date'));
    }

    public function getAddView(Request $request): View
    {
        return view(Vendor::ADD[VIEW]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $this->vendorRepo->update(id: $request['id'], data: ['status' => $request['status']]);
        if ($request['status'] == "approved") {
            Toastr::success(translate('Vendor_has_been_approved_successfully'));
        } else if ($request['status'] == "rejected") {
            Toastr::info(translate('Vendor_has_been_rejected_successfully'));
        } else if ($request['status'] == "suspended") {
            $this->vendorRepo->update(id: $request['id'], data: ['auth_token' => Str::random(80)]);
            Toastr::info(translate('Vendor_has_been_suspended_successfully'));
        }
        return back();
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $sellers = $this->vendorRepo->getListWhere(
            orderBy:['id'=>'desc'],
            searchValue: $request['searchValue'],
            relations: ['orders', 'product'],
            dataLimit: 'all'
        );

        $active = $sellers->where('status','approved')->count();
        $inactive = $sellers->where('status','!=','approved')->count();
        $data = [
            'sellers' => $sellers,
            'search' => $request['searchValue'],
            'active' =>$active,
            'inactive' => $inactive,
        ];
        return Excel::download(new SellerListExport($data),VendorExport::EXPORT_XLSX);
    }

    public function getOrderListView(Request $request, $seller_id): View
    {
        $orders = $this->orderRepo->getListWhere(
            orderBy: ['id'=>'desc'],
            searchValue: $request['searchValue'],
            filters: ['seller_id'=> $seller_id, 'seller_is'=> 'seller'],
            dataLimit:getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
        );
        $seller = $this->vendorRepo->getFirstWhere(params: ['id'=>$seller_id]);
        return view(Vendor::ORDER_LIST[VIEW], compact('orders', 'seller'));
    }

    public function getProductListView(Request $request, $seller_id): View
    {
        $filters = ['seller_id' => $seller_id, 'added_by' => 'seller'];
        $products = $this->productRepo->getListWhere(
            orderBy: ['id' => 'desc'],
            searchValue: $request['searchValue'],
            filters: $filters,
            relations: ['translations'],
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT)
        );
        $seller = $this->vendorRepo->getFirstWhere(params: ['id'=>$seller_id]);
        return view(Vendor::PRODUCT_LIST[VIEW], compact('products', 'seller'));
    }

    public function updateSalesCommission(Request $request, $id): RedirectResponse
    {
        if ($request['status'] == 1 && $request['commission'] == null) {
            Toastr::error(translate('you_did_not_set_commission_percentage_field'));
            return back();
        }
        $this->vendorRepo->update(id: $id, data: ['sales_commission_percentage' => $request['commission_status'] == 1 ? $request['commission'] : null]);
        Toastr::success(translate('Commission_percentage_for_this_seller_has_been_updated'));
        return back();
    }

    public function getOrderDetailsView($order_id, $seller_id): View
    {
        $country_restrict_status = getWebConfig(name: 'delivery_country_restriction');
        $zip_restrict_status = getWebConfig(name: 'delivery_zip_code_area_restriction');

        $countries = $country_restrict_status ? $this->get_delivery_country_array() : COUNTRIES;
        $zip_codes = $zip_restrict_status ? $this->deliveryZipCodeRepo->getListWhere(dataLimit: 'all') : 0;

        $order = $this->orderRepo->getFirstWhere(
            params: ['id'=> $order_id],
            relations: ['shipping','customer'],
        );

        $physical_product = false;
        foreach($order->details as $product){
            if(isset($product->product) && $product->product->product_type == 'physical'){
                $physical_product = true;
            }
        }

        $shipping_method = getWebConfig(name: 'shipping_method');

        $delivery_men = $this->deliveryManRepo->getListWhereIn(
            filters: ['is_active'=>1, 'order_seller' => $order['seller_is'], 'seller_id' => $order['seller_id'], 'shipping_method' => $shipping_method],
            dataLimit: 'all',
        );

        $shipping_address = $this->shippingAddressRepo->getFirstWhere(params: ['id'=>$order['shipping_address']]);
        $total_delivered = $this->orderRepo->getListWhere(
            filters: ['seller_id' => $order['seller_id'], 'seller_is'=> 'seller', 'order_status' => 'delivered', 'order_type' => 'default_type'],
            dataLimit: 'all',
        )->count();

        $linked_orders = $this->orderRepo->getListWhereNotIn(
            filters: ['order_group_id' => $order['order_group_id']],
            whereNotIn: ['order_group_id' => ['def-order-group'],'id' => [$order['id']]],
            dataLimit: 'all',
        );
        if ($order['order_type'] == 'default_type') {
            $orderCount = $this->orderRepo->getListWhereCount(filters: ['customer_id' => $order['customer_id']]);
        } else {
            $orderCount = $this->orderRepo->getListWhereCount(filters: ['customer_id' => $order['customer_id'], 'order_type' => 'POS']);
        }
        return view(Vendor::ORDER_DETAILS[VIEW], compact('order', 'seller_id','delivery_men', 'linked_orders','physical_product',
            'shipping_address','total_delivered', 'countries','zip_codes','zip_restrict_status','country_restrict_status','orderCount'));
    }

    public function getView(Request $request, $id, $tab = null): View|RedirectResponse
    {
        $seller = $this->vendorRepo->getFirstWhere(
            params: ['id'=>$id, 'withCount' => ['orders', 'product']],
            relations: ['orders', 'product']
        );
        $seller?->product?->map(function($product){
            $product['rating'] = $product?->reviews->pluck('rating')->sum();
            $product['rating_count'] = $product->reviews->count();
            $product['single_rating_5'] = 0;
            $product['single_rating_4'] = 0;
            $product['single_rating_3'] = 0;
            $product['single_rating_2'] = 0;
            $product['single_rating_1'] = 0;
            foreach($product->reviews as $review) {
                $rating = $review->rating;
                match ($rating) {
                    5 => $product->single_rating_5++,
                    4 => $product->single_rating_4++,
                    3 => $product->single_rating_3++,
                    2 => $product->single_rating_2++,
                    1 => $product->single_rating_1++,
                };
            }
        });
        $seller['single_rating_5'] = $seller?->product->pluck('single_rating_5')->sum();
        $seller['single_rating_4'] = $seller?->product->pluck('single_rating_4')->sum();
        $seller['single_rating_3'] = $seller?->product->pluck('single_rating_3')->sum();
        $seller['single_rating_2'] = $seller?->product->pluck('single_rating_2')->sum();
        $seller['single_rating_1'] = $seller?->product->pluck('single_rating_1')->sum();
        $seller['total_rating'] = $seller?->product->pluck('rating')->sum();
        $seller['rating_count'] = $seller->product->pluck('rating_count')->sum();
        $seller['average_rating'] = $seller['total_rating'] / ($seller['rating_count'] == 0 ? 1 : $seller['rating_count']);

        if(!isset($seller)){
            Toastr::error(translate('vendor_not_found_It_may_be_deleted'));
            return back();
        }

        if ($tab == 'order') {
            return $this->getOrderListTabView(request:$request, seller:$seller);
        } else if ($tab == 'product') {
            return $this->getProductListTabView(request:$request, seller:$seller);
        } else if ($tab == 'setting') {
            return $this->getSettingListTabView(request:$request, seller:$seller, id:$id);
        } else if ($tab == 'transaction') {
            return $this->getTransactionListTabView(request:$request, seller:$seller);
        } else if ($tab == 'review') {
            return $this->getReviewListTabView(request:$request, seller:$seller);
        }

        return view(Vendor::VIEW[VIEW], [
            'seller' => $seller,
            'current_date' => date('Y-m-d'),
        ]);
    }

    public function getOrderListTabView(Request $request, $seller): View
    {
        $orders = $this->orderRepo->getListWhere(
            orderBy: ['id'=>'desc'],
            searchValue: $request['searchValue'],
            filters: ['seller_id'=> $seller['id'], 'seller_is'=> 'seller', 'order_type'=>'default_type'],
            dataLimit:getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
        );
        $pendingOrder = $this->orderRepo->getListWhere(
            orderBy: ['id'=>'desc'],
            searchValue: $request['searchValue'],
            filters: ['seller_id'=> $seller['id'], 'seller_is'=> 'seller', 'order_type'=>'default_type','order_status'=>'pending'],
            dataLimit: 'all',
        )->count();
        $deliveredOrder = $this->orderRepo->getListWhere(
            orderBy: ['id'=>'desc'],
            searchValue: $request['searchValue'],
            filters: ['seller_id'=> $seller['id'], 'seller_is'=> 'seller', 'order_type'=>'default_type','order_status'=>'delivered'],
            dataLimit: 'all',
        )->count();

        return view(Vendor::VIEW_ORDER[VIEW], compact('seller', 'orders','pendingOrder','deliveredOrder'));
    }

    public function getProductListTabView(Request $request, $seller): View
    {
        $products = $this->productRepo->getListWhere(
            orderBy: ['id' => 'desc'],
            searchValue: $request['searchValue'],
            filters: ['seller_id' => $seller['id'], 'added_by' => 'seller'],
            relations: ['translations'],
            dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT)
        );
        return view(Vendor::VIEW_PRODUCT[VIEW], compact('seller', 'products'));
    }

    public function getSettingListTabView(Request $request, $seller, $id): View
    {
        return view(Vendor::VIEW_SETTING[VIEW], compact('seller'));
    }
    public function updateSetting(Request $request ,$id):RedirectResponse
    {
        if ($request->has('commission')) {
            request()->validate([
                'commission' => 'required|numeric|min:1',
            ]);
            if ($request['commission_status'] == 1 && $request['commission'] == null) {
                Toastr::error(translate('you_did_not_set_commission_percentage_field.'));
            } else {
                $this->vendorRepo->update(id: $id, data: ['sales_commission_percentage' => $request['commission_status'] == 1 ? $request['commission'] : null]);
                Toastr::success(translate('commission_percentage_for_this_seller_has_been_updated.'));
            }
        }
        if ($request->has('gst')) {
            if ($request['gst_status'] == 1 && $request['gst'] == null) {
                Toastr::error(translate('you_did_not_set_GST_number_field.'));
            } else {
                $this->vendorRepo->update(id: $id, data: ['gst' => $request['gst_status'] == 1 ? $request['gst'] : null]);
                Toastr::success(translate('GST_number_for_this_seller_has_been_updated.'));
            }
        }
        if ($request->has('seller_pos_update')) {
            $this->vendorRepo->update(id: $id, data: ['pos_status' => $request->get('seller_pos', 0)]);
            Toastr::success(translate('vendor_pos_permission_updated'));
        }
        return redirect()->back();
    }

    public function getTransactionListTabView(Request $request, $seller): View
    {
        $filters = [
            'seller_is'=>'seller',
            'seller_id'=>$seller['id'],
            'status' => $request['status'] ?? 'all'

        ];
        $transactions = $this->orderTransactionRepo->getListWhere(
            orderBy:['id'=>'desc'],
            searchValue: $request['searchValue'],
            filters: $filters,
            relations: ['order.customer'],
            dataLimit:getWebConfig(name: WebConfigKey::PAGINATION_LIMIT),
        );
        return view(Vendor::VIEW_TRANSACTION[VIEW], compact('seller', 'transactions'));
    }

    public function getReviewListTabView(Request $request, $seller): View
    {
        if ($request->has('searchValue')) {
            $product_id = $this->productRepo->getListWhere(
                searchValue: $request['searchValue'],
                filters: ['added_by'=>'seller', 'seller_id'=>$seller['id']],
                dataLimit: 'all')->pluck('id')->toArray();
            $filtersBy = [
                'product_id' => $product_id,
            ];
            $reviews = $this->reviewRepo->getListWhereIn(
                orderBy:['id'=>'desc'],
                filters: ['added_by' => 'seller'],
                whereInFilters: $filtersBy,
                relations: ['product'],
                nullFields: ['delivery_man_id'],
                dataLimit: getWebConfig(name: 'pagination_limit'));
        } else {
            $reviews = $this->reviewRepo->getListWhereIn(
                orderBy:['id'=>'desc'],
                filters: ['product_user_id' => $seller['id']],
                relations: ['product', 'customer'],
                dataLimit:getWebConfig(name: 'pagination_limit'));
        }
        return view(Vendor::VIEW_REVIEW[VIEW], [
            'seller' => $seller,
            'reviews' => $reviews,
        ]);
    }

    public function getWithdrawView($withdraw_id, $seller_id): View|RedirectResponse
    {
        $withdrawRequest = $this->withdrawRequestRepo->getFirstWhere(params: ['id' => $withdraw_id], relations: ['seller']);
        if ($withdrawRequest) {
            $withdrawalMethod = json_decode($withdrawRequest['withdrawal_method_fields'], true);
            $direction = session('direction');
            return view(Vendor::WITHDRAW_VIEW[VIEW], compact('withdrawRequest', 'withdrawalMethod','direction'));
        }
        Toastr::error(translate('withdraw_request_not_found'));
        return back();
    }

    public function getWithdrawListView(Request $request): View
    {
        $withdrawRequests = $this->withdrawRequestRepo->getListWhereNull(
            orderBy:['id'=>'desc'],
            filters: ['approved' => $request['approved']],
            nullFilters: ['delivery_man_id'],
            relations: ['seller'],
            dataLimit:getWebConfig(name: 'pagination_limit')
        );
        return view(Vendor::WITHDRAW_LIST[VIEW], compact('withdrawRequests'));
    }

    public function exportWithdrawList(Request $request): BinaryFileResponse
    {
        $withdrawRequests = $this->withdrawRequestRepo->getListWhereNull(
            orderBy:['id'=>'desc'],
            filters: ['approved' => $request['approved']],
            nullFilters: ['delivery_man_id'],
            relations: ['seller'],
            dataLimit: 'all'
        );

        $withdrawRequests->map(function ($query) {
            $query->shop_name = isset($query->seller) ? $query->seller->shop->name : '';
            $query->shop_phone = isset($query->seller) ? $query->seller->shop->contact : '';
            $query->shop_address = isset($query->seller) ? $query->seller->shop->address : '';
            $query->shop_email = isset($query->seller) ? $query->seller->email : '';
            $query->withdrawal_amount = setCurrencySymbol(amount: usdToDefaultCurrency(amount: $query->amount), currencyCode: getCurrencyCode(type: 'default'));
            $query->status = $query->approved == 0 ? 'Pending' : ($query->approved == 1 ? 'Approved':'Denied');
            $query->note = $query->transaction_note;
            $query->withdraw_method_name = isset($query->withdraw_method) ? $query->withdraw_method->method_name : '';
            if(!empty($query->withdrawal_method_fields)){
                foreach (json_decode($query->withdrawal_method_fields) as $key=>$field) {
                    $query[$key] = $field;
                }
            }
        });

        $pending = $withdrawRequests->where('approved', 0)->count();
        $approved = $withdrawRequests->where('approved', 1)->count();
        $denied = $withdrawRequests->where('approved', 2)->count();

        return Excel::download(new SellerWithdrawRequest([
                    'withdraw_request'=>$withdrawRequests,
                    'filter' => session('withdraw_status_filter'),
                    'pending'=> $pending,
                    'approved'=> $approved,
                    'denied'=> $denied,
                ]), 'Seller-Withdraw-Request.xlsx'
        );
    }


    public function withdrawStatus(Request $request, $id): RedirectResponse
    {
        $withdrawData = [
            'approved' => $request['approved'],
            'transaction_note' => $request['note'],
        ];

        $withdraw = $this->withdrawRequestRepo->getFirstWhere(params: ['id'=>$id], relations: ['seller']);
        if(!empty($withdraw->seller?->cm_firebase_token)) {
            WithdrawStatusUpdateEvent::dispatch('withdraw_request_status_message','seller',$withdraw->deliveryMan?->app_language ?? getDefaultLanguage(),$request['approved'], $withdraw->seller?->cm_firebase_token);
        }

        if ($request['approved'] == 1) {
            $this->vendorWalletRepo->getFirstWhere(params: ['seller_id'=>$withdraw['seller_id']])->increment('withdrawn', $withdraw['amount']);
            $this->vendorWalletRepo->getFirstWhere(params: ['seller_id'=>$withdraw['seller_id']])->decrement('pending_withdraw', $withdraw['amount']);

            $this->withdrawRequestRepo->update(id: $id, data: $withdrawData);
            Toastr::success(translate('Vendor_Payment_has_been_approved_successfully'));
            return redirect()->route('admin.sellers.withdraw_list');
        }

        $this->vendorWalletRepo->getFirstWhere(params: ['seller_id'=>$withdraw['seller_id']])->increment('total_earning', $withdraw['amount']);
        $this->vendorWalletRepo->getFirstWhere(params: ['seller_id'=>$withdraw['seller_id']])->decrement('pending_withdraw', $withdraw['amount']);
        $this->withdrawRequestRepo->update(id: $id, data: $withdrawData);

        Toastr::info(translate('Vendor_Payment_request_has_been_Denied_successfully'));
        return redirect()->route('admin.sellers.withdraw_list');

    }




}
