<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="Belajar disini cara mendapatkan penghasilan dari bursa saham Amerika. Anda bisa tahu kapan beli, kapan jual & saham apa yang bagus.">
        <meta name="author" content="">

        <meta property="og:title" content="Belajar Trading Saham Amerika - Akela Trading System">
        <meta property="og:type" content="website">
        <meta property="og:description" content="Belajar disini cara mendapatkan penghasilan dari bursa saham Amerika. Anda bisa tahu kapan beli, kapan jual & saham apa yang bagus.">
        <meta property="og:image" content="https://share.tradingnyantai.com/img/akela-og.jpg">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w==" crossorigin="anonymous" />
        <style type="text/css">
            .container {
                width: 70%;
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
                border: 1px solid #4e73df;
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

            table {
                width: 60%
            }

            table > td {
                padding: 5px;
                vertical-align: middle;
                text-align: center;
            }
            
            @media screen and (max-width: 640px) {
                .container {
                    width: 100%;
                    border-radius: 10%;
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
                <p style="text-align: justify;">
                    <h2>Hi {{ $nama_lengkap }}</h2> 
                    <br>
                    <span style="font-size: 14px;">
                        ayo ajak teman ataupun kerabat untuk mengenal Akela Trading System dan dapatkan bonus referal mulai dari Rp 500.000,- ! Caranya sangat mudah, Anda tinggal membagikan referal link yang ada di email ini kepada teman atau kerabat Anda. Untuk informasi lebih lanjut silahkan kunjungi: https://tradingnyantai.com/affiliate
                    </span>
                </p>
                <h4>Berikut adalah Link yang bisa anda bagikan : </h4>
                @foreach ($serviceList as $service)
                    <input type="text" class="form-control" value="{{$service['link']['Website']}}" readonly>
                    <br/>
                    <br/>
                    <table class="table">
                        <tr>
                            <td width="30%">Share Media Sosial : </td>
                            <td>
                                <a title="Share on Facebook" href="https://www.facebook.com/sharer/sharer.php?u={{$service['link']['Facebook']}}&p[title]={{$service['marketing_text']['Facebook']}}" target="_blank">
                                    <img src="http://share.tradingnyantai.com/img/facebook-logo.png" width="30" height="30">
                                </a>
                            </td>
                            <td>
                                <a title="Share on Mail" href="mailto:?body={{$service['marketing_text']['Email']}}" target="_blank">
                                    <img src="http://share.tradingnyantai.com/img/email-logo.png" width="30" height="30">
                                </a>
                            </td>
                            <td>
                                <a title="Share on Telegram" href="https://t.me/share/url?url={{$service['link']['Telegram']}}&text={{$service['marketing_text']['Telegram']}}" target="_blank">
                                    <img src="http://share.tradingnyantai.com/img/telegram-logo.png" width="30" height="30">
                                </a>
                            </td>
                            <td>
                                <a title="Share on Whatsapp" href="https://api.whatsapp.com/send?text={{$service['marketing_text']['Whatsapp']}}" data-action="share/whatsapp/share" target="_blank">
                                    <img src="http://share.tradingnyantai.com/img/whatsapp-logo.png" width="30" height="30">
                                </a>
                            </td>
                            {{-- <td>
                                <a title="Share on LinkedIn" href="https://www.linkedin.com/shareArticle?mini=true&url={{$service['link']['LinkedIn']}}&title={{$service['marketing_text']}}" target="_blank">
                                    <img src="http://share.tradingnyantai.com/img/linkedin-logo.png" width="30" height="30">
                                </a>
                            </td> --}}
                            <td>
                                <a title="Share on Twitter" href="https://twitter.com/intent/tweet?text={{$service['marketing_text']['Twitter']}}" target="_blank">
                                    <img src="http://share.tradingnyantai.com/img/twitter-logo.png" width="30" height="30">
                                </a>
                            </td>
                        </tr>
                    </table>
                @endforeach
            </div>
            <div class="footer">
                <div class="footer-container">
                    <h2>Terima Kasih</h2>
                </div>
            </div>
        </div>
    </body>
</html>