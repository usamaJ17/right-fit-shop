<?php

namespace App\Http\Controllers\Vendor\DeliveryMan;

use App\Contracts\Repositories\DeliveryManRepositoryInterface;
use App\Contracts\Repositories\ReviewRepositoryInterface;
use App\Enums\ViewPaths\Vendor\Dashboard;
use App\Enums\ViewPaths\Vendor\DeliveryMan;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\DeliveryManRequest;
use App\Http\Requests\Vendor\DeliveryManUpdateRequest;
use App\Services\DeliveryManService;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DeliveryManController extends BaseController
{
    /**
     * @param DeliveryManRepositoryInterface $deliveryManRepo
     * @param ReviewRepositoryInterface $reviewRepo
     * @param DeliveryManService $deliveryManService
     */
    public function __construct(
        private readonly DeliveryManRepositoryInterface $deliveryManRepo,
        private readonly ReviewRepositoryInterface      $reviewRepo,
        private readonly DeliveryManService             $deliveryManService,
    )
    {
    }

    /**
     * @param Request|null $request
     * @param string|null $type
     * @return View|Collection|LengthAwarePaginator|callable|RedirectResponse|null
     */
    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        if (!$this->deliveryManService->checkConditions()){
            return redirect()->route(Dashboard::INDEX[ROUTE]);
        }
        return $this->getAddView();
    }

    /**
     * @return View
     * @function index is the starting point of a controller
     */
    public function getAddView(): View
    {
        $telephoneCodes = TELEPHONE_CODES;
        return view(DeliveryMan::INDEX[VIEW], compact('telephoneCodes'));
    }

    /**
     * @param DeliveryManRequest $request
     * @return RedirectResponse
     * @function add  is the adding request data to delivery_men table
     */
    public function add(DeliveryManRequest $request):RedirectResponse
    {
        $this->deliveryManRepo->add($this->deliveryManService->getDeliveryManAddData(
            request:$request,
            addedBy:'seller')
        );
        Toastr::success(translate('Deliveryman_added_successfully'));
        return redirect()->route(DeliveryMan::LIST[ROUTE]);
    }
    /**
     * @param Request $request
     * @return RedirectResponse|View
     * @function getList ,showing the deliveryMan list
     */
    public function getListView(Request $request):RedirectResponse|View
    {
        if (!$this->deliveryManService->checkConditions()){
            return redirect()->route(Dashboard::INDEX[ROUTE]);
        }
        $vendorId = auth('seller')->id();
        $searchValue = $request['search'];
        $deliveryMen = $this->deliveryManRepo->getListWhere(
            orderBy: ['id'=>'desc'],
            searchValue: $searchValue,
            filters: ['seller_id' => $vendorId],
            dataLimit: getWebConfig(name: 'pagination_limit')
        );
        return view(DeliveryMan::LIST[VIEW],compact('deliveryMen','searchValue'));
    }

    /**
     * @param string|int $id
     * @return RedirectResponse|View
     * @function updateView ,showing the deliveryMan update view
     */
    public function getUpdateView(string|int $id):RedirectResponse|View
    {
        if (!$this->deliveryManService->checkConditions()){
            return redirect()->route(Dashboard::INDEX[ROUTE]);
        }
        $telephoneCodes = TELEPHONE_CODES;
        $deliveryMan = $this->deliveryManRepo->getFirstWhere(params:['seller_id' => auth('seller')->id(), 'id' => $id]);
        return view(DeliveryMan::UPDATE[VIEW],compact('deliveryMan','telephoneCodes'));
    }
    /**
     * @param DeliveryManUpdateRequest $request
     * @param string|int $id
     * @return RedirectResponse
     * @function update ,update the deliveryMan data
     */
    public function update(DeliveryManUpdateRequest $request , string|int $id):RedirectResponse
    {
        $deliveryMan = $this->deliveryManRepo->getFirstWhere(params:['seller_id' => auth('seller')->id(), 'id' => $id]);
        $this->deliveryManRepo->update($id,$this->deliveryManService->getDeliveryManUpdateData(
            request:$request,
            addedBy:'seller',
            identityImages: $deliveryMan['identity_image'],
            deliveryManImage: $deliveryMan['image'])
        );
        return redirect()->route(DeliveryMan::LIST[ROUTE]);

    }
    /**
     * @param Request $request
     * @param string|int $id
     * @return JsonResponse
     * @function updateStatus ,update the deliveryMan status
     */
    public function updateStatus(Request $request , string|int $id) : JsonResponse
    {
        $deliveryMan = $this->deliveryManRepo->getFirstWhere(params:['seller_id' => auth('seller')->id(), 'id' => $id]);
        $this->deliveryManRepo->update($deliveryMan['id'], data: ['is_active'=>$request['status']]);
        return response()->json([],200);
    }

    /**
     * @param string|int $id
     * @return RedirectResponse|View
     * @function delete ,update the deliveryMan data
     */
    public function delete(string|int $id) : RedirectResponse|View
    {
        if (!$this->deliveryManService->checkConditions()){
            return redirect()->route(Dashboard::INDEX[ROUTE]);
        }
        $deliveryMan = $this->deliveryManRepo->getFirstWhere(['seller_id' => auth('seller')->id(), 'id' => $id]);
        $this->deliveryManService->deleteImages(deliveryMan: $deliveryMan);
        $this->deliveryManRepo->delete(params:['id'=>$id]);
        Toastr::success(translate('delivery-man_removed').'!');
        return redirect()->back();
    }

    /**
     * @param Request $request
     * @param string|int $id
     * @return RedirectResponse|View
     */
    public function getRatingView(Request $request , string|int $id): RedirectResponse|view
    {
        $deliveryMan = $this->deliveryManRepo->getFirstWhere(params:['seller_id' => auth('seller')->id(), 'id' => $id]);
        if (!$deliveryMan) {
            Toastr::warning(translate('invalid_review').'!');
            return redirect()->route(DeliveryMan::LIST[ROUTE]);
        }
        $searchValue = $request['search'];
        $filters = [
            'delivery_man_id' => $id,
            'from' => $request['from_date'],
            'to' => $request['to_date'],
            'rating' => $request['rating'],
        ];

        $reviews = $this->reviewRepo->getListWhere(
            searchValue:$searchValue,
            filters: $filters,
            relations: ['order'],
            dataLimit: getWebConfig(name: 'pagination_limit'));

        $total = $reviews->total();
        $averageRatting = $reviews->avg('rating');
        $one = $reviews->where('rating', 1)->count();
        $two = $reviews->where('rating', 2)->count();
        $three = $reviews->where('rating', 3)->count();
        $four = $reviews->where('rating', 4)->count();
        $five = $reviews->where('rating', 5)->count();
        return view(DeliveryMan::RATING[VIEW],compact(
            'deliveryMan',
            'searchValue',
            'filters',
            'reviews',
            'averageRatting',
            'total',
            'one',
            'two',
            'three',
            'four',
            'five'
        ));

    }
}
