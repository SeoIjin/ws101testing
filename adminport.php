<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Portal - Barangay Officials Access</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Quicksand:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        html,body{
            height:100%;
            margin:0;
            font-family:"Quicksand", Arial, sans-serif;
            background:#ffffff;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }

        /* layout */
        .page{
            display:flex;
            width:100%;
            height:100vh;
            overflow:hidden;
            align-items:stretch;
        }

        /* left panel */
        .left{
            flex:1.2;
            min-width:420px;
            background:linear-gradient(135deg,#164a43,#0f413b);
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            padding:48px 40px;
            gap:18px;
        }

        .seal{
            width:180px;
            height:180px;
            border-radius:50%;
            background:#ffffff;
            display:flex;
            align-items:center;
            justify-content:center;
            box-shadow:0 12px 36px rgba(0,0,0,0.18);
            flex-shrink:0;
        }
        .seal img{ width:128x; height:128px; object-fit:contain; display:block; }

        .left-title{
            color:#ffffff;
            font-family:"Montserrat",sans-serif;
            font-size:34px; /* enlarged */
            font-weight:700;
            margin:8px 0 6px;
            text-align:center;
        }
        .left-sub{
            color:rgba(255,255,255,0.92);
            font-size:16px; /* enlarged */
            text-align:center;
            max-width:480px;
            line-height:1.45;
        }

        .left-pill{
            margin-top:28px;
            width:85%;
            max-width:520px;
            background:rgba(255,255,255,0.06);
            border-radius:34px;
            padding:14px 20px;
            color:#fff;
            font-weight:700;
            display:flex;
            align-items:center;
            justify-content:center;
            box-shadow:0 8px 20px rgba(0,0,0,0.12);
            text-align:center;
            font-size:16px; /* enlarged */
        }

        /* right panel */
        .right{
            flex:0.95;
            min-width:480px;
            background:#ffffff;
            padding:56px 52px; /* increased padding */
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:flex-start;
            gap:12px;
        }

        .brand{
            display:flex;
            gap:12px;
            align-items:center;
            width:100%;
            margin-bottom:6px;
        }
        .brand .shield{
            width:700px;
            height:100px;
            border-radius:12px;
            background:#e9f6ef;
            display:flex;
            align-items:center;
            justify-content:center;
            flex:0 0 70px;
        }
        .brand .shield img{ width:88px; height:88px; }

        .heading{
            font-family:"Montserrat",sans-serif;
            font-size:40px; /* enlarged */
            font-weight:700;
            color:#0f1720;
            margin:0;
            line-height:1;
        }
        .subheading{
            font-size:15px; /* enlarged */
            color:#6b7376;
            margin-top:4px;
        }
        .desc{
            font-size:20px; /* enlarged */
            color:#6b7376;
            margin-bottom:10px;
            max-width:520px;
            line-height:1.5;
        }

        /* form sizing and spacing adjustments */
        .form{ width:100%; max-width:520px; }
        .field-label{
            font-size:20px; /* enlarged */
            font-weight:700;
            color:#222;
            margin:14px 0 8px 0;
            width:100%;
        }

        .input-wrap{
            position:relative;
            margin-bottom:14px;
            width:100%;
        }

        /* input: increased left padding to clear icon, consistent height and larger text */
        .input{
            width:85%;
            padding:14px 45px 14px 44px; /* extra right padding to accommodate larger eye button */
            border-radius:12px;
            border:0;
            background:#f1f2f3;
            font-size:16px; /* enlarged */
            color:#222;
            outline:none;
            box-shadow:0 1px 0 rgba(0,0,0,0.02) inset;
            min-height:52px; /* taller inputs */
            line-height:1.2;
        }
        .input:focus{ box-shadow:0 0 0 4px rgba(22,88,70,0.06); }

        /* icon placement aligned with input padding */
        .input-icon{
            position:absolute;
            left:14px;
            top:50%;
            transform:translateY(-50%);
            width:22px;
            height:22px;
            opacity:.9;
            pointer-events:none;
        }
        /* eye positioned at the very edge of the input box */
        .eye-btn{
            position:absolute;
            right:8px; /* edge */
            top:50%;
            transform:translateY(-50%);
            width:40px;  /* enlarged */
            height:40px; /* enlarged */
            border-radius:8px;
            background:transparent;
            border:0;
            cursor:pointer;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:0;
        }
        .eye-btn img{ width:20px; height:20px; display:block; opacity:.95; }

        .forgot{
            font-size:14px;
            color:#6b7376;
            margin-top:8px;
            text-align:right;
            width:100%;
        }

        .actions{
            width:100%;
            display:flex;
            flex-direction:column;
            gap:12px;
            margin-top:18px;
        }
        .primary{
            width:100%;
            padding:16px 20px; /* larger */
            border-radius:12px; /* larger */
            border:0;
            background:linear-gradient(180deg,#0f413b,#164a43);
            color:#fff;
            font-weight:800;
            font-size:18px; /* larger */
            cursor:pointer;
            box-shadow:0 12px 34px rgba(8,18,15,0.14);
        }
        .secondary{
            width:100%;
            padding:14px 20px; /* larger */
            border-radius:12px; /* larger */
            border:0;
            background:#f5f6fa;
            color:#163832;
            font-size:16px; /* larger */
            font-weight:800;
            cursor:pointer;
        }

        .link-row{ width:100%; display:flex; justify-content:center; margin-top:8px; }
        .link-row a{ color:#0f6b5f; text-decoration:none; font-weight:700; font-size:14px; }

        /* responsive */
        @media (max-width:1000px){
            .page{ flex-direction:column; }
            .left{ min-height:300px; width:100%; padding:28px; order:1; }
            .right{ width:100%; min-width:0; padding:28px; order:2; align-items:stretch; }
            .left-pill{ width:calc(100% - 56px); }
            .form{ max-width:100%; }
        }
    </style>
</head>
<body>
    <div class="page" role="main">
        <div class="left" aria-hidden>
            <div class="seal" title="Municipal Seal">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/29/Seal_of_the_National_Government.svg/512px-Seal_of_the_National_GOVERNMENT.svg.png" alt="seal">
            </div>
            <div class="left-title">Welcome to eBCsH</div>
            <div class="left-sub">aBCM Official! Please sign in to access your dashboard and manage community requests.</div>
            <div class="left-pill">Admin Portal Barangay Officials Only.</div>
        </div>

        <aside class="right" aria-label="Admin sign in">
            <div class="brand">
                <div class="shield" aria-hidden>
                    <img src="https://cdn-icons-png.flaticon.com/128/10703/10703030.png" alt="">
                </div>
                <div>
                    <div class="heading">Admin Sign In</div>
                    <div class="subheading">aBCM Official Dashboard Access</div>
                </div>
            </div>

            <div class="desc">Please sign in to access your dashboard and manage community requests.</div>

            <form class="form" autocomplete="off" onsubmit="return false;">
                <div class="field-label">Email Address</div>
                <div class="input-wrap">
                    <img class="input-icon" src="https://img.icons8.com/ios-filled/50/9aa0a6/new-post.png" alt="">
                    <input id="officialEmail" class="input" type="email" placeholder="official@barangay.gov.ph" required>
                </div>

                <div class="field-label">Password</div>
                <div class="input-wrap">
                    <img class="input-icon" src="https://img.icons8.com/ios-filled/50/9aa0a6/lock-2.png" alt="">
                    <input id="adminPassword" class="input" type="password" placeholder="Enter your secure password" required>
                    <button type="button" class="eye-btn" id="eyeToggle" aria-label="toggle password">
                        <img src="https://cdn-icons-png.flaticon.com/128/2767/2767146.png" alt="toggle">
                    </button>
                </div>

                <div class="forgot">Forgot your password?</div>

                <div class="actions">
                    <button class="primary" type="submit">Sign in to Dashboard</button>
                    <button class="secondary" type="button" onclick="location.href='sign-in.php'">Back to Citizen Portal</button>
                </div>

                <div class="link-row"><a href="#">Need help signing in?</a></div>
            </form>
        </aside>
    </div>

    <script>
        (function(){
            const pwd = document.getElementById('adminPassword');
            const eye = document.getElementById('eyeToggle');
            if (!pwd || !eye) return;
            const openIcon = 'https://cdn-icons-png.flaticon.com/128/709/709612.png';
            const closedIcon = 'https://cdn-icons-png.flaticon.com/128/2767/2767146.png';
            eye.addEventListener('click', function () {
                const showing = pwd.type === 'password';
                pwd.type = showing ? 'text' : 'password';
                const img = eye.querySelector('img');
                if (img) img.src = showing ? openIcon : closedIcon;
            });
        })();
    </script>
</body>
</html>