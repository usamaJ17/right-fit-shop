@extends('theme-views.layouts.app')
@section('title', translate('vendor_Apply').' | '.$web_config['name']->value.' '.translate('ecommerce'))
@section('content')
    <main class="main-content d-flex flex-column gap-3 py-3 mb-sm-5">
        <div class="container">
            <div class="card">
                <div class="card-body p-sm-4">
                    <div class="row justify-content-between gy-4">
                        <div class="col-lg-4">
                            <div class="bg-light p-3 p-sm-4 rounded h-100">
                                <div class="d-flex justify-content-center">
                                    <div class="ext-center">
                                        <h2 class="mb-2 text-capitalize">{{translate('vendor_registration')}}</h2>
                                        <p>{{translate('create_your_own_store').'.'.translate('already_have_store').'?'}}
                                            <a class="text-primary fw-bold" href="{{route('vendor.auth.login')}}">{{translate('login')}}</a>
                                        </p>
                                        <div class="my-4 text-center">
                                            <img width="243" src="{{theme_asset('assets/img/media/seller-registration.png')}}" loading="lazy" alt="" class="dark-support">
                                        </div>
                                        <p class="text-primary">{{translate('open_your_and_start_selling').'.'.translate('create_your_own_business')}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8 col-xl-7">
                            <form id="seller-registration" action="{{route('shop.apply')}}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="wizard">
                                    <h3 class="text-capitalize">{{translate('vendor_info')}}</h3>
                                    <section>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label class="text-capitalize" for="firstName">{{translate('first_name')}} *</label>
                                                    <input class="form-control" type="text" id="firstName" name="f_name" value="{{old('f_name')}}" placeholder="{{translate('ex') .':'.translate('jhon')}}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label class="text-capitalize" for="lastName">{{translate('last_name')}} *</label>
                                                    <input class="form-control" type="text" id="lastName" name="l_name" value="{{old('l_name')}}" placeholder="{{translate('ex').':'.translate('doe')}}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label for="email2">{{translate('email')}} *</label>
                                                    <input class="form-control" type="email" id="email2"  name="email" value="{{old('email')}}" placeholder="{{translate('enter_email')}}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label for="tel">{{translate('phone')}} *</label>
                                                    <input class="form-control" type="tel" id="tel" name="phone" value="{{old('phone')}}" placeholder="{{translate('enter_phone_number')}}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label for="password">{{translate('password')}} *</label>
                                                    <div class="input-inner-end-ele">
                                                        <input class="form-control" type="password" id="passwordID"  name="password" value="{{old('password')}}" placeholder="{{translate('enter_password')}}" required>
                                                        <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label for="confirm_password">{{translate('confirm_password')}} *</label>
                                                    <div class="input-inner-end-ele">
                                                        <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="{{translate('confirm_password')}}" required>
                                                        <i class="bi bi-eye-slash-fill togglePassword"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="media gap-3 align-items-center">
                                                    <div class="upload-file">
                                                        <input type="file" class="upload-file__input" name="image" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                                        <div class="upload-file__img">
                                                            <div class="temp-img-box">
                                                                <div class="d-flex align-items-center flex-column gap-2">
                                                                    <i class="bi bi-upload fs-30"></i>
                                                                    <div class="fs-12 text-muted text-capitalize">{{translate('upload_file')}}</div>
                                                                </div>
                                                            </div>
                                                            <img src="#" class="dark-support img-fit-contain border" alt="" hidden>
                                                        </div>
                                                    </div>

                                                    <div class="media-body d-flex flex-column gap-1 upload-img-content">
                                                        <h5 class="text-uppercase mb-1 text-capitalize">{{translate('vendor_image')}}</h5>
                                                        <div class="text-muted text-capitalize">{{translate('image_ration').' '.'1:1'}}</div>
                                                        <div class="text-muted">
                                                            {{translate('NB')}}: {{translate('image_size_must_be_within').' '.'2 MB'}}
                                                            <br>
                                                            {{translate('NB')}}: {{translate('image_type_must_be_within').' '.'.jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff'}}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <h3 class="text-capitalize">{{translate('shop_info')}}</h3>
                                    <section>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label class="text-capitalize" for="storeName">{{translate('store_name')}} *</label>
                                                    <input class="form-control" type="text" id="storeName" name="shop_name" placeholder="{{translate('ex').' : '.translate('halar')}}" value="{{old('shop_name')}}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group mb-4">
                                                    <label for="storeAddress">{{translate('Store_Address')}} *</label>
                                                    <input class="form-control" type="text" id="storeAddress" name="shop_address" value="{{old('shop_address')}}" placeholder="{{translate('ex').' : '.'Shop-12 Road-8' }}" required>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 mb-4">
                                                <div class="d-flex flex-column gap-3 align-items-center">
                                                    <div class="upload-file">
                                                        <input type="file" class="upload-file__input" name="banner" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                                        <div class="upload-file__img style--two">
                                                            <div class="temp-img-box">
                                                                <div class="d-flex align-items-center flex-column gap-2">
                                                                    <i class="bi bi-upload fs-30"></i>
                                                                    <div class="fs-12 text-muted text-capitalize">{{translate('upload_file')}}</div>
                                                                </div>
                                                            </div>
                                                            <img src="" class="dark-support img-fit-contain border" alt="" hidden>
                                                        </div>
                                                    </div>

                                                    <div class="text-center">
                                                        <h5 class="text-uppercase mb-1 text-capitalize">{{translate('store_banner')}}</h5>
                                                        <div class="text-muted text-capitalize">{{translate('image_ratio').' '.'3:1'}}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            @if(theme_root_path() == "theme_aster")
                                            <div class="col-lg-6 mb-4">
                                                <div class="d-flex flex-column gap-3 align-items-center">
                                                    <div class="upload-file">
                                                        <input type="file" class="upload-file__input" name="bottom_banner" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                                        <div class="upload-file__img style--two">
                                                            <div class="temp-img-box">
                                                                <div class="d-flex align-items-center flex-column gap-2">
                                                                    <i class="bi bi-upload fs-30"></i>
                                                                    <div class="fs-12 text-muted text-capitalize">{{translate('upload_file')}}</div>
                                                                </div>
                                                            </div>
                                                            <img src="" class="dark-support img-fit-contain border" alt="" hidden>
                                                        </div>
                                                    </div>

                                                    <div class="text-center">
                                                        <h5 class="text-uppercase mb-1 text-capitalize">{{translate('store_secondary_banner')}}</h5>
                                                        <div class="text-muted">{{translate('image_ratio').' '.'3:1'}}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            <div class="col-lg-6 mb-4">
                                                <div class="d-flex flex-column gap-3 align-items-center">
                                                    <div class="upload-file">
                                                        <input type="file" class="upload-file__input" name="logo" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                                                        <div class="upload-file__img">
                                                            <div class="temp-img-box">
                                                                <div class="d-flex align-items-center flex-column gap-2">
                                                                    <i class="bi bi-upload fs-30"></i>
                                                                    <div class="fs-12 text-muted text-capitalize">{{translate('upload_file')}}</div>
                                                                </div>
                                                            </div>
                                                            <img src="" class="dark-support img-fit-contain border" alt="" hidden>
                                                        </div>
                                                    </div>

                                                    <div class="text-center">
                                                        <h5 class="text-uppercase mb-1 text-capitalize">{{translate('store_logo')}}</h5>
                                                        <div class="text-muted">{{translate('image_ratio').' '.'1:1'}}</div>
                                                    </div>
                                                </div>
                                            </div>
                                            @if($web_config['recaptcha']['status'] == 1)
                                                <div class="col-12">
                                                    <div id="recaptcha-element-seller-register" class="w-100 mt-4" data-type="image"></div>
                                                    <br/>
                                                </div>
                                            @else
                                            <div class="col-12">
                                                <div class="row py-2 mt-4">
                                                    <div class="col-6 pr-2">
                                                        <input type="text" class="form-control border __h-40" name="default_recaptcha_id_seller_regi" value=""
                                                            placeholder="{{ translate('enter_captcha_value') }}" autocomplete="off" required>
                                                    </div>
                                                    <div class="col-6 input-icons mb-2 rounded bg-white">
                                                        <a id="re-captcha-vendor-register" class="d-flex align-items-center align-items-center">
                                                            <img src="{{ route('vendor.auth.recaptcha', ['tmp'=>1]).'?captcha_session_id=sellerRecaptchaSessionKey' }}" class="input-field rounded __h-40" alt="" id="default_recaptcha_id_regi">
                                                            <i class="bi bi-arrow-repeat icon cursor-pointer p-2"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif

                                            <div class="col-12">
                                                <label class="custom-checkbox">
                                                    <input id="acceptTerms" name="acceptTerms" type="checkbox" required>
                                                    {{translate('i_agree_with_the')}} <a target="_blank" href="{{route('terms')}}">{{translate('terms_and_condition').'.'}}</a>
                                                </label>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
@push('script')

    @if($web_config['recaptcha']['status'] == '1')
        <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
        <script>
            "use strict";
            let onloadCallback = function () {
                let reg_id = grecaptcha.render('recaptcha-element-seller-register', {'sitekey': '{{ $web_config['recaptcha']['site_key'] }}'});
                let login_id = grecaptcha.render('recaptcha_element_seller_login', {'sitekey': '{{ $web_config['recaptcha']['site_key'] }}'});

                $('#recaptcha-element-seller-register').attr('data-reg-id', reg_id);
                $('#recaptcha_element_seller_login').attr('data-login-id', login_id);
            };
        </script>
    @else
        <script>
            "use strict";
            function vendorNumericRecaptcha() {
                $('#re-captcha-vendor-register').on('click',function (){
                    let genUrl = "{{ route('vendor.auth.recaptcha', ['tmp'=>':dummy-id']) }}";
                    genUrl = genUrl.replace(":dummy-id", Math.random());
                    genUrl = genUrl + '?captcha_session_id=sellerRecaptchaSessionKey';
                    document.getElementById('default_recaptcha_id_regi').src = genUrl;
                })
            }
            vendorNumericRecaptcha();
        </script>
    @endif
    <script src="{{theme_asset('assets/plugins/jquery-step/jquery.validate.min.js')}}"></script>
    <script src="{{theme_asset('assets/plugins/jquery-step/jquery.steps.min.js')}}"></script>
    <script>
        "use strict";
        $(document).ready(function(){
            $('#seller-registration [href="#next"]').text("{{ translate('next') }}");
            $('#seller-registration [href="#previous"]').text("{{ translate('previous') }}");
        });
        let form = $("#seller-registration");
        form.validate({
            errorPlacement: function errorPlacement(error, element) { element.before(error); },
            rules: {
                confirm_password: {
                    equalTo: "#passwordID"
                }
            }
        });
        form.children(".wizard").steps({
            headerTag: "h3",
            bodyTag: "section",
            onStepChanging: function (event, currentIndex, newIndex)
            {
                $('[href="#next"]').text("{{ translate('next') }}");
                $('[href="#previous"]').text("{{ translate('previous') }}");
                $('[href="#finish"]').text("{{ translate('finish') }}");
                $('[href="#finish"]').addClass('disabled');

                $('#acceptTerms').click(function(){
                    if ($(this).is(':checked')) {
                        $('[href="#finish"]').removeClass('disabled');
                    }else{
                        $('[href="#finish"]').addClass('disabled');
                    }
                });
                if (currentIndex > newIndex) {
                    return true;
                }
                if (currentIndex < newIndex) {
                    form.find('.body:eq(' + newIndex + ') label.error').remove();
                    form.find('.body:eq(' + newIndex + ') .error').removeClass('error');
                }
                form.validate().settings.ignore = ":disabled,:hidden";
                @if($web_config['recaptcha']['status'] != '1')
                    vendorNumericRecaptcha();
                @endif
                return form.valid();
            },
            onFinishing: function (event, currentIndex)
            {
                form.validate().settings.ignore = ":disabled";
                return form.valid();
            },
            onFinished: function (event, currentIndex)
            {
                @if($web_config['recaptcha']['status'] == '1')
                if(currentIndex > 0){
                    let response = grecaptcha.getResponse($('#recaptcha-element-seller-register').attr('data-reg-id'));
                    if (response.length === 0) {
                        toastr.error("{{translate('please_check_the_recaptcha')}}");
                    }else{
                        $('#seller-registration').submit();
                    }
                }
                @else
                $('#seller-registration').submit();
                @endif
            }
        });
    </script>
@endpush
