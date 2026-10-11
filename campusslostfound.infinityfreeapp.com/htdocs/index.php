<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>CampusLost | UPTM Lost & Found</title>

<style>

:root{
    --blue:#3b82f6;
    --blue-dark:#2563eb;
    --blue-light:#172554;

    --navy:#f1f5f9;
    --text:#e2e8f0;
    --muted:#94a3b8;

    --border:#26364d;
    --bg:#0b1220;
    --white:#111c2e;

    --green:#22c55e;
    --red:#ef4444;
}

*{
    box-sizing:border-box;
    margin:0;
    padding:0;
}

html{
    scroll-behavior:smooth;
}

body{
    font-family:Arial, Helvetica, sans-serif;
    color:var(--text);
    background:var(--bg);
    line-height:1.6;
}

a{
    text-decoration:none;
}

/* =========================================
   NAVIGATION
========================================= */
nav{
    min-height:76px;
    height:auto;

    background:#111c2e;

    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:10px 4%;

    position:sticky;
    top:0;
    z-index:1000;

    border-bottom:1px solid #26364d;
    box-shadow:0 3px 18px rgba(0,0,0,.25);

    flex-wrap:nowrap;
}

.logo{
    display:flex;
    align-items:center;
    gap:10px;

    color:#f1f5f9;
    font-size:20px;
    font-weight:800;

    white-space:nowrap;
    flex-shrink:0;
}

.navlinks{
    display:flex;
    align-items:center;
    justify-content:flex-end;

    gap:22px;

    flex-wrap:nowrap;
    margin-left:auto;
}

.navlinks a{
    white-space:nowrap;
}

.quick-actions{
    background:#111c2e;
}

.quick-card{
    color:#e2e8f0;
}

.quick-card:hover{
    background:#172337;
}

.how{
    background:#0b1220;
}

.step-card{
    background:#111c2e;
    border-color:#26364d;
}

.about{
    background:#0f1b2d;
}

.benefit{
    background:#111c2e;
    border-color:#26364d;
}

.items-section{
    background:#0b1220;
}

.item{
    background:#111c2e;
    border-color:#26364d;
}

.item-img{
    background:linear-gradient(145deg,#162238,#101827);
}

.cta-wrapper{
    background:#0b1220;
}


.logo{
    display:flex;
    align-items:center;
    gap:11px;
    color:var(--navy);
    font-size:21px;
    font-weight:800;
}

.logo-icon{
    width:43px;
    height:43px;
    border-radius:12px;
    display:grid;
    place-items:center;
    color:white;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    box-shadow:0 5px 15px rgba(37,99,235,.25);
}

.logo span:last-child{
    color:var(--blue);
}

.navlinks{
    display:flex;
    align-items:center;
    gap:27px;
}

.navlinks > a:not(.btn){
    color:#334155;
    font-size:14px;
    font-weight:600;
    transition:.2s;
}

.navlinks > a:not(.btn):hover{
    color:var(--blue);
}

.btn{
    display:inline-flex;
    justify-content:center;
    align-items:center;
    gap:7px;
    padding:11px 19px;
    border-radius:9px;
    font-size:14px;
    font-weight:700;
    transition:.25s;
    cursor:pointer;
}

.login{
    color:var(--blue);
    border:1px solid #bfdbfe;
    background:white;
}

.login:hover{
    background:#eff6ff;
}

.register,
.primary{
    color:white;
    background:var(--blue);
    border:1px solid var(--blue);
}

.register:hover,
.primary:hover{
    background:var(--blue-dark);
    transform:translateY(-1px);
}

/* =========================================
   HERO
========================================= */

.hero{
    min-height:620px;
    position:relative;
    display:flex;
    align-items:center;
    overflow:hidden;

    background:
        linear-gradient(
            90deg,
            rgba(7,27,52,.96) 0%,
            rgba(15,56,113,.92) 45%,
            rgba(37,99,235,.66) 100%
        ),
        url('https://images.unsplash.com/photo-1562774053-701939374585?auto=format&fit=crop&w=1800&q=85')
        center/cover;
}

.hero::after{
    content:"";
    position:absolute;
    width:430px;
    height:430px;
    border-radius:50%;
    background:rgba(255,255,255,.06);
    right:-100px;
    top:-140px;
}

.hero-content{
    position:relative;
    z-index:2;
    width:100%;
    padding:80px 7%;
}

.hero-inner{
    max-width:720px;
}

.eyebrow{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.18);
    color:#dbeafe;
    padding:8px 13px;
    border-radius:50px;
    font-size:12px;
    font-weight:800;
    letter-spacing:1.4px;
    margin-bottom:22px;
}

.hero h1{
    color:white;
    font-size:64px;
    line-height:1.04;
    letter-spacing:-2px;
    margin-bottom:21px;
}

.hero h1 span{
    color:#93c5fd;
}

.hero-description{
    color:#dbeafe;
    max-width:625px;
    font-size:18px;
    margin-bottom:31px;
}

.hero-description strong{
    color:white;
}

.hero-actions{
    display:flex;
    flex-wrap:wrap;
    gap:13px;
}

.hero-actions .btn{
    padding:14px 22px;
}

.hero .primary{
    background:white;
    color:var(--blue);
    border-color:white;
}

.hero .primary:hover{
    background:#eff6ff;
}

.outline{
    color:white;
    border:1px solid rgba(255,255,255,.7);
    background:rgba(255,255,255,.08);
}

.outline:hover{
    background:rgba(255,255,255,.16);
}

.hero-note{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    margin-top:29px;
    color:#bfdbfe;
    font-size:13px;
}

.hero-note span{
    display:flex;
    align-items:center;
    gap:6px;
}

/* =========================================
   QUICK ACTIONS
========================================= */

.quick-wrapper{
    position:relative;
    z-index:5;
    width:86%;
    max-width:1180px;
    margin:-58px auto 0;
}

.quick-actions{
    background:white;
    border-radius:18px;
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    overflow:hidden;
    box-shadow:0 15px 45px rgba(15,39,71,.13);
    border:1px solid #edf2f7;
}

.quick-card{
    padding:27px 28px;
    display:flex;
    gap:16px;
    align-items:center;
    color:var(--text);
    transition:.25s;
}

.quick-card:not(:last-child){
    border-right:1px solid var(--border);
}

.quick-card:hover{
    background:#f8fbff;
}

.quick-icon{
    width:52px;
    min-width:52px;
    height:52px;
    border-radius:13px;
    display:grid;
    place-items:center;
    font-size:23px;
}

.quick-card:nth-child(1) .quick-icon{
    background:#fee2e2;
}

.quick-card:nth-child(2) .quick-icon{
    background:#dcfce7;
}

.quick-card:nth-child(3) .quick-icon{
    background:#dbeafe;
}

.quick-card h3{
    color:var(--navy);
    font-size:16px;
    margin-bottom:2px;
}

.quick-card p{
    color:var(--muted);
    font-size:13px;
}

/* =========================================
   GENERAL SECTIONS
========================================= */

section{
    padding:85px 7%;
}

.section-label{
    text-align:center;
    color:var(--blue);
    font-size:12px;
    font-weight:800;
    letter-spacing:1.8px;
    text-transform:uppercase;
    margin-bottom:8px;
}

.section-title{
    text-align:center;
    color:var(--navy);
    font-size:36px;
    line-height:1.2;
    margin-bottom:11px;
}

.section-sub{
    max-width:620px;
    margin:0 auto 45px;
    text-align:center;
    color:var(--muted);
    font-size:15px;
}

/* =========================================
   HOW IT WORKS
========================================= */

.how{
    background:white;
    padding-top:110px;
}

.steps{
    max-width:1100px;
    margin:auto;
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:25px;
}

.step-card{
    position:relative;
    padding:35px 27px;
    border-radius:17px;
    border:1px solid var(--border);
    background:white;
    text-align:center;
    transition:.25s;
}

.step-card:hover{
    transform:translateY(-5px);
    box-shadow:0 13px 35px rgba(15,39,71,.08);
    border-color:#bfdbfe;
}

.step-number{
    width:47px;
    height:47px;
    margin:0 auto 19px;
    border-radius:13px;
    display:grid;
    place-items:center;
    color:white;
    font-weight:800;
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
    box-shadow:0 7px 17px rgba(37,99,235,.22);
}

.step-icon{
    font-size:29px;
    margin-bottom:11px;
}

.step-card h3{
    color:var(--navy);
    font-size:19px;
    margin-bottom:9px;
}

.step-card p{
    color:var(--muted);
    font-size:14px;
}

/* =========================================
   ABOUT / BENEFITS
========================================= */

.about{
    background:#eef6ff;
}

.about-layout{
    max-width:1150px;
    margin:auto;
    display:grid;
    grid-template-columns:.9fr 1.1fr;
    gap:70px;
    align-items:center;
}

.about-left .section-label,
.about-left .section-title{
    text-align:left;
}

.about-left .section-title{
    margin-bottom:18px;
}

.about-left > p{
    color:var(--muted);
    font-size:15px;
    margin-bottom:25px;
}

.about-badge{
    display:inline-block;
    padding:8px 13px;
    background:white;
    color:var(--blue);
    font-weight:700;
    font-size:13px;
    border-radius:8px;
    border:1px solid #dbeafe;
}

.benefit-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}

.benefit{
    background:white;
    padding:23px;
    border-radius:14px;
    border:1px solid #dbeafe;
    box-shadow:0 5px 20px rgba(15,39,71,.04);
}

.benefit-icon{
    font-size:25px;
    margin-bottom:11px;
}

.benefit h3{
    color:var(--navy);
    font-size:16px;
    margin-bottom:6px;
}

.benefit p{
    color:var(--muted);
    font-size:13px;
}

/* =========================================
   RECENT ITEMS
========================================= */

.items-section{
    background:white;
}

.items{
    max-width:1150px;
    margin:auto;
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:21px;
}

.item{
    background:white;
    border:1px solid var(--border);
    border-radius:15px;
    overflow:hidden;
    transition:.25s;
}

.item:hover{
    transform:translateY(-5px);
    box-shadow:0 13px 32px rgba(15,39,71,.09);
}

.item-img{
    height:175px;
    display:grid;
    place-items:center;
    font-size:65px;
    background:linear-gradient(145deg,#eff6ff,#f8fafc);
}

.item-body{
    padding:18px;
}

.item-top{
    display:flex;
    justify-content:space-between;
    align-items:start;
    gap:8px;
    margin-bottom:9px;
}

.item h3{
    color:var(--navy);
    font-size:17px;
}

.badge{
    padding:4px 8px;
    border-radius:20px;
    font-size:10px;
    font-weight:800;
}

.lost{
    color:#b91c1c;
    background:#fee2e2;
}

.found{
    color:#15803d;
    background:#dcfce7;
}

.meta{
    color:var(--muted);
    font-size:12px;
    margin-top:5px;
}

.items-button{
    text-align:center;
    margin-top:35px;
}

.view-btn{
    display:inline-flex;
    color:var(--blue);
    font-weight:700;
    font-size:14px;
    border:1px solid #bfdbfe;
    padding:11px 20px;
    border-radius:9px;
    transition:.2s;
}

.view-btn:hover{
    background:#eff6ff;
}

/* =========================================
   CTA
========================================= */

.cta-wrapper{
    padding:20px 7% 90px;
    background:white;
}

.cta{
    max-width:1150px;
    margin:auto;
    position:relative;
    overflow:hidden;
    background:linear-gradient(120deg,#0b2447,#1d4ed8);
    border-radius:22px;
    padding:55px 60px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:35px;
    color:white;
}

.cta::after{
    content:"";
    position:absolute;
    width:250px;
    height:250px;
    border-radius:50%;
    background:rgba(255,255,255,.06);
    right:-70px;
    top:-90px;
}

.cta-text{
    position:relative;
    z-index:2;
}

.cta h2{
    font-size:34px;
    line-height:1.2;
    margin-bottom:10px;
}

.cta p{
    color:#dbeafe;
}

.cta .btn{
    position:relative;
    z-index:2;
    white-space:nowrap;
    background:white;
    color:var(--blue);
    padding:14px 22px;
}

.cta .btn:hover{
    background:#eff6ff;
}

/* =========================================
   FOOTER
========================================= */

footer{
    background:#071b34;
    color:#cbd5e1;
    padding:55px 7% 20px;
}

.footer-grid{
    max-width:1150px;
    margin:auto;
    display:grid;
    grid-template-columns:2fr 1fr 1fr 1fr;
    gap:45px;
}

.footer-brand{
    display:flex;
    align-items:center;
    gap:10px;
    color:white;
    font-size:20px;
    font-weight:800;
    margin-bottom:13px;
}

.footer-brand-icon{
    width:39px;
    height:39px;
    display:grid;
    place-items:center;
    background:var(--blue);
    border-radius:10px;
}

.footer-about{
    max-width:300px;
    color:#94a3b8;
    font-size:13px;
}

footer h3{
    color:white;
    font-size:15px;
    margin-bottom:14px;
}

footer a{
    display:block;
    color:#94a3b8;
    font-size:13px;
    margin:8px 0;
    transition:.2s;
}

footer a:hover{
    color:white;
}

.copyright{
    max-width:1150px;
    margin:40px auto 0;
    border-top:1px solid rgba(255,255,255,.1);
    padding-top:18px;
    color:#64748b;
    font-size:12px;
    display:flex;
    justify-content:space-between;
    gap:15px;
}

/* =========================================
   RESPONSIVE
========================================= */

@media(max-width:950px){

    .navlinks > a:not(.btn){
        display:none;
    }

    .hero h1{
        font-size:52px;
    }

    .about-layout{
        grid-template-columns:1fr;
        gap:40px;
    }

    .items{
        grid-template-columns:1fr 1fr;
    }

    .footer-grid{
        grid-template-columns:1fr 1fr;
    }

}

@media(max-width:700px){

    nav{
        padding:0 5%;
    }

    .logo{
        font-size:18px;
    }

    .login{
        display:none;
    }

    .hero{
        min-height:580px;
    }

    .hero-content{
        padding:65px 5% 90px;
    }

    .hero h1{
        font-size:43px;
        letter-spacing:-1px;
    }

    .hero-description{
        font-size:16px;
    }

    .quick-wrapper{
        width:90%;
        margin-top:-45px;
    }

    .quick-actions{
        grid-template-columns:1fr;
    }

    .quick-card:not(:last-child){
        border-right:0;
        border-bottom:1px solid var(--border);
    }

    section{
        padding:70px 5%;
    }

    .how{
        padding-top:90px;
    }

    .steps{
        grid-template-columns:1fr;
    }

    .benefit-grid{
        grid-template-columns:1fr;
    }

    .section-title{
        font-size:30px;
    }

    .cta-wrapper{
        padding:10px 5% 70px;
    }

    .cta{
        padding:40px 30px;
        flex-direction:column;
        align-items:flex-start;
    }

    .cta h2{
        font-size:29px;
    }

}

@media(max-width:520px){

    .navlinks{
        gap:8px;
    }

    .register{
        padding:10px 13px;
    }

    .hero h1{
        font-size:39px;
    }

    .hero-actions{
        flex-direction:column;
        align-items:stretch;
    }

    .hero-actions .btn{
        width:100%;
    }

    .items{
        grid-template-columns:1fr;
    }

    .footer-grid{
        grid-template-columns:1fr;
    }

    .copyright{
        flex-direction:column;
    }

}

/* =========================================
   FULL DARK THEME OVERRIDE
========================================= */

body{
    background:#08111f;
    color:#e5edf8;
}

/* NAVBAR */
nav{
    background:#0c1728;
    border-bottom:1px solid #22324a;
    box-shadow:0 3px 18px rgba(0,0,0,.25);
}

.logo{
    color:#f8fafc;
}

.navlinks > a:not(.btn){
    color:#cbd5e1;
}

.navlinks > a:not(.btn):hover{
    color:#60a5fa;
}

.login{
    background:#111e32;
    border-color:#334155;
    color:#60a5fa;
}

.login:hover{
    background:#17263d;
}

/* QUICK ACTIONS */

.quick-actions{
    background:#101c2e;
    border-color:#26364d;
    box-shadow:0 15px 45px rgba(0,0,0,.30);
}

.quick-card{
    color:#e2e8f0;
}

.quick-card:not(:last-child){
    border-color:#26364d;
}

.quick-card:hover{
    background:#16243a;
}

.quick-card h3{
    color:#f8fafc;
}

.quick-card p{
    color:#94a3b8;
}

/* HOW IT WORKS */

.how{
    background:#08111f;
}

.section-title{
    color:#f1f5f9;
}

.section-sub{
    color:#94a3b8;
}

.section-label{
    color:#60a5fa;
}

.step-card{
    background:#101c2e;
    border-color:#26364d;
}

.step-card:hover{
    border-color:#3b82f6;
    box-shadow:0 13px 35px rgba(0,0,0,.25);
}

.step-card h3{
    color:#f1f5f9;
}

.step-card p{
    color:#94a3b8;
}

/* ABOUT */

.about{
    background:#0c1728;
}

.about-left > p{
    color:#94a3b8;
}

.about-badge{
    background:#101c2e;
    color:#60a5fa;
    border-color:#26364d;
}

.benefit{
    background:#101c2e;
    border-color:#26364d;
    box-shadow:0 5px 20px rgba(0,0,0,.18);
}

.benefit h3{
    color:#f1f5f9;
}

.benefit p{
    color:#94a3b8;
}

/* RECENT ITEMS */

.items-section{
    background:#08111f;
}

.item{
    background:#101c2e;
    border-color:#26364d;
}

.item:hover{
    box-shadow:0 13px 32px rgba(0,0,0,.28);
}

.item-img{
    background:linear-gradient(
        145deg,
        #16263e,
        #0d192a
    );
}

.item h3{
    color:#f1f5f9;
}

.meta{
    color:#94a3b8;
}

.view-btn{
    color:#60a5fa;
    border-color:#334e72;
}

.view-btn:hover{
    background:#13233a;
}

/* CTA AREA */

.cta-wrapper{
    background:#08111f;
}

/* FOOTER */

footer{
    background:#050c17;
}
</style>
</head>

<body>

<!-- =====================================
     NAVIGATION
===================================== -->

<nav>

    <a href="index.php" class="logo">
        <span class="logo-icon">🎓</span>
        <span>Campus<span>Lost</span></span>
    </a>

    <div class="navlinks">

        <a href="index.php">Home</a>
        <a href="#how">How It Works</a>
        <a href="#items">Recent Items</a>
        <a href="#about">About</a>

        <a class="btn login" href="login.php">
            Login
        </a>

        <a class="btn register" href="register.php">
            Register
        </a>

    </div>

</nav>


<!-- =====================================
     HERO
===================================== -->

<header class="hero">

    <div class="hero-content">

        <div class="hero-inner">

            <div class="eyebrow">
                🎓 UPTM LOST & FOUND PLATFORM
            </div>

            <h1>
                Lost Something?<br>
                <span>Let's Find It.</span>
            </h1>

            <p class="hero-description">
                <strong>
                    Find what you've lost. Return what you've found.
                </strong>
                <br>
                CampusLost makes it easier for UPTM students and staff
                to report, search, and recover lost items around campus.
            </p>

            <div class="hero-actions">

                <a class="btn primary" href="login.php">
                    📌 Report Lost Item
                </a>

                <a class="btn outline" href="login.php">
                    📦 Report Found Item
                </a>

            </div>

            <div class="hero-note">

                <span>✓ Simple to use</span>

                <span>✓ Campus community</span>

                <span>✓ Secure claims</span>

            </div>

        </div>

    </div>

</header>


<!-- =====================================
     QUICK ACTIONS
===================================== -->

<div class="quick-wrapper">

    <div class="quick-actions">

        <a href="login.php" class="quick-card">

            <div class="quick-icon">
                🔍
            </div>

            <div>
                <h3>Lost Something?</h3>
                <p>Report your missing item.</p>
            </div>

        </a>


        <a href="login.php" class="quick-card">

            <div class="quick-icon">
                📦
            </div>

            <div>
                <h3>Found Something?</h3>
                <p>Help return it to its owner.</p>
            </div>

        </a>


        <a href="login.php" class="quick-card">

            <div class="quick-icon">
                🔎
            </div>

            <div>
                <h3>Search Items</h3>
                <p>Browse reported lost and found items.</p>
            </div>

        </a>

    </div>

</div>


<!-- =====================================
     HOW IT WORKS
===================================== -->

<section id="how" class="how">

    <div class="section-label">
        Simple Process
    </div>

    <h2 class="section-title">
        How CampusLost Works
    </h2>

    <p class="section-sub">
        Report, search, and claim your item in just a few simple steps.
    </p>


    <div class="steps">

        <div class="step-card">

            <div class="step-number">
                01
            </div>

            <div class="step-icon">
                📝
            </div>

            <h3>Report an Item</h3>

            <p>
                Report a lost or found item by providing its
                description, category, location, and other useful details.
            </p>

        </div>


        <div class="step-card">

            <div class="step-number">
                02
            </div>

            <div class="step-icon">
                🔍
            </div>

            <h3>Search & Match</h3>

            <p>
                Browse reported items and search using relevant
                information to find a possible match.
            </p>

        </div>


        <div class="step-card">

            <div class="step-number">
                03
            </div>

            <div class="step-icon">
                🤝
            </div>

            <h3>Claim & Recover</h3>

            <p>
                Submit a claim for a found item and provide the
                necessary information to verify ownership.
            </p>

        </div>

    </div>

</section>


<!-- =====================================
     ABOUT / BENEFITS
===================================== -->

<section id="about" class="about">

    <div class="about-layout">

        <div class="about-left">

            <div class="section-label">
                About CampusLost
            </div>

            <h2 class="section-title">
                Making Lost & Found Easier on Campus
            </h2>

            <p>
                CampusLost is a web-based lost and found platform
                designed to help the UPTM community manage lost
                and found items in one convenient place.
            </p>

            <p>
                Instead of relying only on word of mouth or scattered
                messages, users can report items, search for possible
                matches, and submit claims through a simple system.
            </p>

            <div class="about-badge">
                🎓 Built for the UPTM Community
            </div>

        </div>


        <div class="benefit-grid">

            <div class="benefit">

                <div class="benefit-icon">
                    🔎
                </div>

                <h3>Easy to Use</h3>

                <p>
                    A simple interface that makes reporting
                    and searching for items convenient.
                </p>

            </div>


            <div class="benefit">

                <div class="benefit-icon">
                    📍
                </div>

                <h3>Campus-Based</h3>

                <p>
                    Designed specifically for lost and found
                    activities within the campus community.
                </p>

            </div>


            <div class="benefit">

                <div class="benefit-icon">
                    🛡️
                </div>

                <h3>Secure Claims</h3>

                <p>
                    Item claims can be reviewed before a
                    found item is returned to a claimant.
                </p>

            </div>


            <div class="benefit">

                <div class="benefit-icon">
                    🔔
                </div>

                <h3>Stay Updated</h3>

                <p>
                    Notifications help users stay informed
                    about activity related to their reports.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =====================================
     RECENT ITEMS
===================================== -->

<section id="items" class="items-section">

    <div class="section-label">
        Latest Reports
    </div>

    <h2 class="section-title">
        Recently Reported Items
    </h2>

    <p class="section-sub">
        See examples of lost and found items reported by the campus community.
    </p>


    <div class="items">


        <!-- ITEM 1 -->

        <div class="item">

            <div class="item-img">
                🎒
            </div>

            <div class="item-body">

                <div class="item-top">

                    <h3>Backpack</h3>

                    <span class="badge lost">
                        LOST
                    </span>

                </div>

                <div class="meta">
                    📍 Library
                </div>

                <div class="meta">
                    📅 14 May 2024
                </div>

            </div>

        </div>


        <!-- ITEM 2 -->

        <div class="item">

            <div class="item-img">
                📱
            </div>

            <div class="item-body">

                <div class="item-top">

                    <h3>iPhone 13</h3>

                    <span class="badge found">
                        FOUND
                    </span>

                </div>

                <div class="meta">
                    📍 Cafeteria
                </div>

                <div class="meta">
                    📅 13 May 2024
                </div>

            </div>

        </div>


        <!-- ITEM 3 -->

        <div class="item">

            <div class="item-img">
                🔑
            </div>

            <div class="item-body">

                <div class="item-top">

                    <h3>Keys</h3>

                    <span class="badge found">
                        FOUND
                    </span>

                </div>

                <div class="meta">
                    📍 Parking Area
                </div>

                <div class="meta">
                    📅 11 May 2024
                </div>

            </div>

        </div>


        <!-- ITEM 4 -->

        <div class="item">

            <div class="item-img">
                👛
            </div>

            <div class="item-body">

                <div class="item-top">

                    <h3>Wallet</h3>

                    <span class="badge lost">
                        LOST
                    </span>

                </div>

                <div class="meta">
                    📍 Lecture Hall
                </div>

                <div class="meta">
                    📅 10 May 2024
                </div>

            </div>

        </div>

    </div>


    <div class="items-button">

        <a href="login.php" class="view-btn">
            View All Items →
        </a>

    </div>

</section>


<!-- =====================================
     CALL TO ACTION
===================================== -->

<div class="cta-wrapper">

    <div class="cta">

        <div class="cta-text">

            <h2>
                Lost something on campus?
            </h2>

            <p>
                Your item might already have been reported.
                Start searching with CampusLost.
            </p>

        </div>


        <a href="login.php" class="btn">
            🔍 Search Lost & Found
        </a>

    </div>

</div>


<!-- =====================================
     FOOTER
===================================== -->

<footer>

    <div class="footer-grid">


        <div>

            <div class="footer-brand">

                <div class="footer-brand-icon">
                    🎓
                </div>

                CampusLost

            </div>

            <p class="footer-about">
                A simple lost and found platform connecting
                the UPTM community and helping belongings
                find their way home.
            </p>

        </div>


        <div>

            <h3>Quick Links</h3>

            <a href="index.php">
                Home
            </a>

            <a href="#how">
                How It Works
            </a>

            <a href="#items">
                Recent Items
            </a>

            <a href="#about">
                About
            </a>

        </div>


        <div>

            <h3>Account</h3>

            <a href="login.php">
                Login
            </a>

            <a href="register.php">
                Register
            </a>

            <a href="login.php">
                Report Lost Item
            </a>

            <a href="login.php">
                Report Found Item
            </a>

        </div>


        <div>

            <h3>CampusLost</h3>

            <a href="#how">
                How It Works
            </a>

            <a href="#about">
                About CampusLost
            </a>

            <a href="login.php">
                Search Items
            </a>

        </div>

    </div>


    <div class="copyright">

        <span>
            © 2026 CampusLost. All rights reserved.
        </span>

        <span>
            UPTM Lost & Found Platform
        </span>

    </div>

</footer>

</body>
</html>