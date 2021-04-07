<!DOCTYPE html>
<html>
    <head>
        <style type="text/css">
            .container {
                width: 70%;
                text-align: center;
                margin: auto;
                font-family: sans-serif;
            }
            
            .header, .footer {
                text-align: center;
                background-color: #4e73df
            }
            
            .header-container, .footer-container {
                margin: auto;
                padding: 1rem 0.5rem;
                color: #ffffff;
            }
            
            .header-container img, .footer-container p {
                vertical-align: middle;
            }
            
            .content {
                padding: 2rem 1rem;
                text-align: center;
                border: 1px solid #4e73df;
            }

            .btn {
                display: inline-block;
                font-weight: 400;
                color: #858796;
                text-align: center;
                vertical-align: middle;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
                background-color: transparent;
                border: 1px solid transparent;
                padding: 0.375rem 0.75rem;
                font-size: 1rem;
                line-height: 1.5;
                border-radius: 0.35rem;
                transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            @media (prefers-reduced-motion: reduce) {
                .btn {
                    transition: none;
                }
            }

            .btn:hover {
                color: #858796;
                text-decoration: none;
            }

            .btn:focus, .btn.focus {
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            }

            a.btn.disabled,
                fieldset:disabled a.btn {
                pointer-events: none;
            }

            .btn-primary {
                color: #ffffff;
                background-color: #4e73df;
                border-color: #4e73df;
            }

            .btn-primary:hover {
                color: #ffffff;
                background-color: #2e59d9;
                border-color: #2653d4;
            }

            .btn-primary:focus, .btn-primary.focus {
                color: #ffffff;
                background-color: #2e59d9;
                border-color: #2653d4;
                box-shadow: 0 0 0 0.2rem rgba(105, 136, 228, 0.5);
            }

            .btn-primary.disabled, .btn-primary:disabled {
                color: #fff;
                background-color: #4e73df;
                border-color: #4e73df;
            }

            .btn-primary:not(:disabled):not(.disabled):active, .btn-primary:not(:disabled):not(.disabled).active,
            .show > .btn-primary.dropdown-toggle {
                color: #fff;
                background-color: #2653d4;
                border-color: #244ec9;
            }

            .btn-primary:not(:disabled):not(.disabled):active:focus, .btn-primary:not(:disabled):not(.disabled).active:focus,
            .show > .btn-primary.dropdown-toggle:focus {
                box-shadow: 0 0 0 0.2rem rgba(105, 136, 228, 0.5);
            }

            .form-control {
                display: block;
                width: 80%;
                height: calc(1.5em + 0.75rem + 2px);
                padding: 0.375rem 0.75rem;
                font-size: 1rem;
                font-weight: 400;
                line-height: 1.5;
                color: #6e707e;
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid #d1d3e2;
                border-radius: 0.35rem;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            @media (prefers-reduced-motion: reduce) {
                .form-control {
                    transition: none;
                }
            }

            .form-control::-ms-expand {
                background-color: transparent;
                border: 0;
            }

            .form-control:-moz-focusring {
                color: transparent;
                text-shadow: 0 0 0 #6e707e;
            }

            .form-control:focus {
                color: #6e707e;
                background-color: #fff;
                border-color: #bac8f3;
                outline: 0;
                box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            }

            .form-control::-webkit-input-placeholder {
                color: #858796;
                opacity: 1;
            }

            .form-control::-moz-placeholder {
                color: #858796;
                opacity: 1;
            }

            .form-control:-ms-input-placeholder {
                color: #858796;
                opacity: 1;
            }

            .form-control::-ms-input-placeholder {
                color: #858796;
                opacity: 1;
            }

            .form-control::placeholder {
                color: #858796;
                opacity: 1;
            }

            .form-control:disabled, .form-control[readonly] {
                background-color: #eaecf4;
                opacity: 1;
            }
            
            @media screen and (max-width: 640px) {
                .container {
                    width: 100%;
                    border-radius: 10%;
                }
                .btn {
                    display: inline-block;
                    font-weight: 400;
                    color: #858796;
                    text-align: center;
                    vertical-align: middle;
                    -webkit-user-select: none;
                    -moz-user-select: none;
                    -ms-user-select: none;
                    user-select: none;
                    background-color: transparent;
                    border: 1px solid transparent;
                    padding: 0.375rem 0.75rem;
                    font-size: 1rem;
                    line-height: 1.5;
                    border-radius: 0.35rem;
                    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
                }

                @media (prefers-reduced-motion: reduce) {
                    .btn {
                        transition: none;
                    }
                }

                .btn:hover {
                    color: #858796;
                    text-decoration: none;
                }

                .btn:focus, .btn.focus {
                    outline: 0;
                    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
                }

                a.btn.disabled,
                    fieldset:disabled a.btn {
                    pointer-events: none;
                }

                .btn-primary {
                    color: #ffffff;
                    background-color: #4e73df;
                    border-color: #4e73df;
                }

                .btn-primary:hover {
                    color: #ffffff;
                    background-color: #2e59d9;
                    border-color: #2653d4;
                }

                .btn-primary:focus, .btn-primary.focus {
                    color: #ffffff;
                    background-color: #2e59d9;
                    border-color: #2653d4;
                    box-shadow: 0 0 0 0.2rem rgba(105, 136, 228, 0.5);
                }

                .btn-primary.disabled, .btn-primary:disabled {
                    color: #fff;
                    background-color: #4e73df;
                    border-color: #4e73df;
                }

                .btn-primary:not(:disabled):not(.disabled):active, .btn-primary:not(:disabled):not(.disabled).active,
                .show > .btn-primary.dropdown-toggle {
                    color: #fff;
                    background-color: #2653d4;
                    border-color: #244ec9;
                }

                .btn-primary:not(:disabled):not(.disabled):active:focus, .btn-primary:not(:disabled):not(.disabled).active:focus,
                .show > .btn-primary.dropdown-toggle:focus {
                    box-shadow: 0 0 0 0.2rem rgba(105, 136, 228, 0.5);
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="header-container">
                    <h1>Akela Affiliate System</h1>
                </div>
            </div>
            <div class="content">
                <h2>Hello {{ $nama_lengkap }}</h2>
                <br/>
                <h4>This is <b>Affiliate Link</b> You Can Share:</h4>
                <br/>
                <center>
                    @foreach ($vendorList as $vendor)
                            <h2><b>{{$vendor->name}}</b></h2>
                        @foreach ($serviceList[$vendor->id] as $service)
                            <h4>{{$service['service_name']}}</h4>
                            <input type="text" class="form-control" value="{{$service['link_affiliate']}}" readonly>
                            <i style="margin-top: 5px; font-size: 15px;">You will get {{$service['commission']}} Per Sale</i>
                            <br/>
                            <br/>
                        @endforeach
                    @endforeach
                </center>
            </div>
            <div class="footer">
                <div class="footer-container">
                    <h2>Thank You For Your Registration</h2>
                </div>
            </div>
        </div>
    </body>
</html>