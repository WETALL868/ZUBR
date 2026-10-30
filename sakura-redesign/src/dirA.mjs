// Направление A «Витрина»: сдержанная предметная фотография, Literata + Onest, тёплые нейтральные поверхности.
import { asset, icon, rub, num, esc, STORE, P, CATEGORIES, BRANDS, logoSize, CE285A_COMPAT, CE285A_SPECS, stockLabel, colorDot, page } from './common.mjs';

const FAM = ['Literata', 'Onest'];
const CSS = `
.A{--paper:#F7F5F2;--tile:#F0EDE9;--ink:#1F1D1B;--ink2:#46423E;--muted:#77716B;--line:#E6E1DB;--red:#C5302D;--redd:#A82623;--reds:#FBEFEE;--ok:#2F7A4C;
  background:#fff;color:var(--ink);font:400 15px/24px Onest,sans-serif}
.A .serif{font-family:Literata,serif}
.A .wrap{width:1248px;margin:0 auto}
.A .util{height:36px;border-bottom:1px solid var(--line);font-size:13px;color:var(--muted)}
.A .util .wrap{display:flex;align-items:center;justify-content:space-between;height:36px}
.A .util .l{display:flex;gap:6px;align-items:center}.A .util .r{display:flex;gap:24px}
.A .hdr .wrap{display:flex;align-items:center;gap:32px;height:80px}
.A .logo{display:flex;align-items:center;gap:12px;flex:none}
.A .mono{width:38px;height:38px;border-radius:9px;background:var(--red);color:#fff;display:flex;align-items:center;justify-content:center;font:600 16px/1 Literata,serif;letter-spacing:.02em}
.A .logo b{display:block;font:600 19px/22px Literata,serif;letter-spacing:.005em}
.A .logo small{display:block;font-size:11.5px;line-height:16px;color:var(--muted);letter-spacing:.02em}
.A .search{flex:1;height:46px;border:1px solid var(--line);border-radius:10px;display:flex;align-items:center;gap:10px;padding:0 6px 0 14px;color:var(--muted);background:#fff}
.A .search span{flex:1;font-size:14.5px}
.A .search .go{height:34px;padding:0 14px;border-radius:7px;background:var(--ink);color:#fff;font-size:13.5px;font-weight:500;display:flex;align-items:center}
.A .phone b{display:block;font-weight:600;font-size:15px;line-height:20px}.A .phone small{display:block;font-size:12px;line-height:16px;color:var(--muted)}
.A .acts{display:flex;gap:6px}
.A .act{position:relative;width:64px;height:52px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;font-size:11.5px;line-height:14px;color:var(--ink2);border-radius:10px}
.A .badge{position:absolute;top:3px;left:36px;min-width:18px;height:18px;border-radius:9px;background:var(--red);color:#fff;font-size:11px;font-weight:600;line-height:18px;text-align:center;padding:0 5px}
.A .nav{border-bottom:1px solid var(--line)}
.A .nav .wrap{display:flex;align-items:center;gap:28px;height:52px}
.A .catbtn{display:flex;align-items:center;gap:8px;height:38px;padding:0 16px 0 12px;border-radius:9px;background:var(--ink);color:#fff;font-weight:500;font-size:14px}
.A .nav a.l{font-size:14.5px;font-weight:500;color:var(--ink2)}
.A .nav .find{margin-left:auto;display:flex;align-items:center;gap:7px;color:var(--red);font-weight:600;font-size:14.5px}
.A .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:46px;padding:0 22px;border-radius:10px;background:var(--red);color:#fff;font-weight:600;font-size:15px;border:0}
.A .btn.s{height:40px;padding:0 16px;font-size:14px;border-radius:9px}
.A .btn.ghost{background:#fff;color:var(--ink);border:1px solid #D6D0C9}
.A .btn.line{background:transparent;color:var(--ink);border:1px solid #CFC8C0}
.A .link{color:var(--red);font-weight:500;display:inline-flex;align-items:center;gap:6px;font-size:14.5px}
.A .eyebrow{font-size:12.5px;line-height:16px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:10px}
.A .eyebrow:before{content:"";width:22px;height:2px;background:var(--red)}
.A h2.sec{font:500 28px/36px Literata,serif;margin:0;letter-spacing:-.005em}
.A .sechead{display:flex;align-items:flex-end;justify-content:space-between;margin:56px 0 20px}
.A .hero{margin-top:24px;display:grid;grid-template-columns:520px 1fr;height:452px;border-radius:16px;overflow:hidden;background:var(--paper)}
.A .hero .txt{padding:48px 44px 76px 52px;display:flex;flex-direction:column}
.A .hero h1{font:500 40px/48px Literata,serif;letter-spacing:-.012em;margin:16px 0 14px}
.A .hero p{color:var(--ink2);margin:0 0 24px;font-size:16px;line-height:25px}
.A .hero .cta{display:flex;gap:12px}
.A .hero .ph{background:#ECE8E3 url() center/cover}
.A .hero .ph img{width:100%;height:100%;object-fit:cover;object-position:70% 60%}
.A .finder{position:relative;margin:-34px 28px 0;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 10px 30px rgba(40,30,20,.07);padding:18px 18px 18px 26px;display:grid;grid-template-columns:200px 220px 1fr 210px;gap:12px;align-items:center}
.A .finder .t b{display:block;font:500 18px/24px Literata,serif}.A .finder .t small{display:block;font-size:12.5px;line-height:18px;color:var(--muted)}
.A .field{height:48px;border:1px solid var(--line);border-radius:10px;display:flex;align-items:center;justify-content:space-between;padding:0 14px;font-size:14.5px;color:var(--ink)}
.A .field label{display:block;font-size:11.5px;line-height:14px;color:var(--muted)}
.A .field .v{display:flex;flex-direction:column;justify-content:center}
.A .field .ph2{color:var(--muted)}
.A .cats{display:grid;grid-template-columns:repeat(6,1fr);gap:16px}
.A .cat .im{height:168px;border-radius:12px;background:var(--tile);overflow:hidden}
.A .cat .im img{width:100%;height:100%;object-fit:cover}
.A .cat b{display:block;font-weight:500;font-size:15px;line-height:20px;margin-top:12px}
.A .cat small{display:block;font-size:13px;line-height:18px;color:var(--muted);margin-top:2px}
.A .brands{display:grid;grid-template-columns:236px repeat(7,1fr) 116px;grid-template-rows:76px;align-items:center;border:1px solid var(--line);border-radius:14px;margin-top:40px}
.A .brands .h{padding:0 20px 0 24px;font-weight:600;font-size:14px;line-height:19px;white-space:nowrap}
.A .brands .h small{display:block;font-weight:400;color:var(--muted);font-size:12.5px}
.A .brands .b{height:44px;display:flex;align-items:center;justify-content:center;border-left:1px solid var(--line)}
.A .brands .bmore{border-left:1px solid var(--line);height:44px;display:flex;align-items:center;justify-content:center;font-size:13.5px;color:var(--ink2);font-weight:500;gap:4px}
.A .tabs{display:flex;gap:6px}
.A .pill{height:34px;padding:0 14px;border-radius:17px;border:1px solid var(--line);font-size:13.5px;display:inline-flex;align-items:center;color:var(--ink2)}
.A .pill.on{background:var(--ink);border-color:var(--ink);color:#fff}
.A .grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.A .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.A .card{border:1px solid var(--line);border-radius:14px;background:#fff;display:flex;flex-direction:column;overflow:hidden}
.A .card .im{position:relative;height:164px;padding:20px 30px 8px;display:flex;align-items:center;justify-content:center}
.A .card .im img{max-height:108px;max-width:200px;object-fit:contain}
.A .fav{position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:18px;border:1px solid var(--line);background:#fff;display:flex;align-items:center;justify-content:center;color:var(--ink2)}
.A .card .bd{padding:4px 18px 18px;display:flex;flex-direction:column;flex:1}
.A .card .for{font-size:12.5px;line-height:18px;color:var(--muted)}
.A .card h3{margin:4px 0 6px;font-weight:500;font-size:15px;line-height:21px;height:42px;overflow:hidden}
.A .card .sp{font-size:13px;line-height:18px;color:var(--ink2);display:flex;align-items:center;gap:8px}
.A .dot{width:9px;height:9px;border-radius:5px;display:inline-block}
.A .card .pr{display:flex;align-items:baseline;justify-content:space-between;margin:14px 0 12px}
.A .card .pr b{font-size:20px;line-height:26px;font-weight:600;letter-spacing:-.01em}
.A .st{font-size:12.5px;line-height:16px;display:inline-flex;align-items:center;gap:6px;color:var(--ok)}
.A .st:before{content:"";width:7px;height:7px;border-radius:4px;background:currentColor}
.A .st.order{color:#9A6A12}.A .st.out{color:var(--muted)}
.A .card .buy{display:flex;gap:8px;margin-top:auto}
.A .qty{height:40px;border:1px solid var(--line);border-radius:9px;display:flex;align-items:center;flex:none}
.A .qty span{width:34px;height:38px;display:flex;align-items:center;justify-content:center;color:var(--ink2)}
.A .qty i{font-style:normal;width:26px;text-align:center;font-weight:500}
.A .card .buy .btn{flex:1}
.A .help{margin-top:64px;background:var(--paper);border-radius:16px;padding:40px 48px;display:grid;grid-template-columns:420px 1fr;gap:48px}
.A .help h3{font:500 26px/34px Literata,serif;margin:0 0 10px}
.A .help p{margin:0 0 22px;color:var(--ink2)}
.A .steps{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.A .steps div{border-top:1px solid #DCD5CD;padding-top:16px;font-size:14px;line-height:21px;color:var(--ink2)}
.A .steps b{display:block;font:500 22px/28px Literata,serif;color:var(--red);margin-bottom:8px}
.A .svc{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin:48px 0 64px}
.A .svc div{display:grid;grid-template-columns:28px 1fr;gap:12px;font-size:13.5px;line-height:20px;color:var(--muted)}
.A .svc b{display:block;color:var(--ink);font-weight:600;font-size:14.5px;margin-bottom:2px}
.A .svc .ico{color:var(--red);margin-top:2px}
.A .ftr{position:relative;background:var(--paper);border-top:1px solid var(--line);overflow:hidden}
.A .ftr .bgimg{position:absolute;left:0;right:0;bottom:-4px;height:250px;opacity:.38}
.A .ftr .bgimg img{width:100%;height:100%;object-fit:cover;object-position:center bottom}
.A .ftr .wrap{position:relative}
.A .fcols{display:grid;grid-template-columns:300px 1.15fr 1fr 1fr 260px;gap:32px;padding:48px 0 140px}
.A .fcols h4{margin:0 0 14px;font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);font-weight:500}
.A .fcols a.logo{display:flex}.A .fcols a{display:block;font-size:14.5px;line-height:28px;color:var(--ink2)}
.A .fcols p{margin:14px 0 0;font-size:13.5px;line-height:21px;color:var(--muted)}
.A .fbot{position:relative;border-top:1px solid rgba(120,110,100,.18);background:linear-gradient(rgba(247,245,242,.55),rgba(247,245,242,.92));padding:0 0;display:flex;justify-content:space-between;align-items:center;height:56px;font-size:12.5px;color:var(--muted)}
.A .fbot div{display:flex;gap:20px}
.A .crumbs{display:flex;gap:8px;align-items:center;font-size:13px;color:var(--muted);margin:22px 0 14px}
.A .crumbs .ico{color:#B3ACA4}
/* каталог */
.A .ttl{display:flex;align-items:baseline;gap:14px}
.A .ttl h1{font:500 36px/44px Literata,serif;margin:0;letter-spacing:-.01em}
.A .ttl span{color:var(--muted);font-size:15px}
.A .subcats{display:flex;gap:8px;margin:18px 0 28px}
.A .cl{display:grid;grid-template-columns:264px 1fr;gap:32px}
.A .side .grp{border-top:1px solid var(--line);padding:16px 0 18px}
.A .side .grp:first-child{border-top:0;padding-top:0}
.A .side h5{margin:0 0 12px;font-size:14px;font-weight:600;display:flex;justify-content:space-between;align-items:center}
.A .chk{display:flex;align-items:center;gap:10px;font-size:14px;line-height:22px;padding:5px 0;color:var(--ink2)}
.A .chk i{width:18px;height:18px;border-radius:5px;border:1.5px solid #C9C2BA;flex:none;display:flex;align-items:center;justify-content:center;color:#fff}
.A .chk.on i{background:var(--ink);border-color:var(--ink)}
.A .chk em{font-style:normal;margin-left:auto;color:var(--muted);font-size:13px}
.A .sw{display:flex;gap:8px;flex-wrap:wrap}
.A .sw span{height:34px;padding:0 12px 0 8px;border:1px solid var(--line);border-radius:17px;display:flex;align-items:center;gap:7px;font-size:13px}
.A .range{display:flex;gap:8px}.A .range span{flex:1;height:40px;border:1px solid var(--line);border-radius:9px;display:flex;align-items:center;padding:0 12px;font-size:14px;color:var(--muted)}
.A .bar{height:4px;border-radius:2px;background:var(--line);margin:16px 6px 6px;position:relative}
.A .bar i{position:absolute;left:8%;right:30%;top:0;bottom:0;background:var(--ink);border-radius:2px}
.A .tb{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.A .chips{display:flex;gap:8px;align-items:center}
.A .chip{height:32px;padding:0 10px 0 12px;border-radius:16px;background:var(--paper);display:flex;align-items:center;gap:6px;font-size:13px;color:var(--ink2)}
.A .sort{display:flex;gap:10px;align-items:center;font-size:14px;color:var(--ink2)}
.A .sort .sel{height:38px;border:1px solid var(--line);border-radius:9px;padding:0 12px;display:flex;align-items:center;gap:6px}
.A .vt{display:flex;border:1px solid var(--line);border-radius:9px;overflow:hidden}.A .vt span{width:38px;height:36px;display:flex;align-items:center;justify-content:center;color:var(--muted)}.A .vt span.on{background:var(--paper);color:var(--ink)}
.A .more{display:flex;justify-content:center;gap:16px;align-items:center;margin:28px 0 64px}
.A .pg{display:flex;gap:4px}.A .pg span{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--ink2)}.A .pg span.on{background:var(--ink);color:#fff}
/* товар */
.A .pp{display:grid;grid-template-columns:96px 600px 1fr;grid-template-areas:'t m i' '. d i';gap:20px 24px;align-items:start}
.A .thumbs{grid-area:t;display:flex;flex-direction:column;gap:10px}
.A .thumbs span{height:80px;border:1px solid var(--line);border-radius:10px;display:flex;align-items:center;justify-content:center;padding:6px;background:#fff;overflow:hidden}
.A .thumbs span.on{border:1.5px solid var(--ink)}
.A .thumbs img{max-height:66px;object-fit:contain}
.A .main{grid-area:m;height:470px;border:1px solid var(--line);border-radius:16px;display:flex;align-items:center;justify-content:center;padding:40px;position:relative}
.A .main img{max-height:330px;object-fit:contain}
.A .main .cap{position:absolute;left:20px;bottom:16px;font-size:12px;color:var(--muted)}
.A .main .zoom{position:absolute;right:16px;bottom:14px;font-size:12.5px;color:var(--ink2);display:flex;gap:6px;align-items:center}
.A .info{grid-area:i;padding-left:20px}
.A .pdesc{grid-area:d;padding:8px 4px 0}.A .pdesc p{margin:0 0 14px;color:var(--ink2)}.A .pdesc ul{margin:0;padding:0;list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:8px 24px;font-size:14px;color:var(--ink2)}.A .pdesc li{display:flex;gap:8px;align-items:center}.A .pdesc li .ico{color:var(--ok)}
.A .info .top{display:flex;justify-content:space-between;align-items:center;font-size:13px;color:var(--muted)}
.A .info .ia{display:flex;gap:6px}
.A .ib{width:38px;height:38px;border-radius:19px;border:1px solid var(--line);display:flex;align-items:center;justify-content:center;color:var(--ink2)}
.A .info h1{font:500 30px/38px Literata,serif;letter-spacing:-.01em;margin:10px 0 12px}
.A .meta{font-size:13.5px;line-height:22px;color:var(--ink2)}
.A .meta span{color:var(--muted)}
.A .facts{display:grid;grid-template-columns:1fr 1fr;border:1px solid var(--line);border-radius:12px;margin:18px 0 0;overflow:hidden}
.A .facts div{padding:11px 16px;border-top:1px solid var(--line);font-size:14.5px;font-weight:500;line-height:20px}
.A .facts div:nth-child(-n+2){border-top:0}.A .facts div:nth-child(2n){border-left:1px solid var(--line)}
.A .facts small{display:block;font-size:12px;color:var(--muted);font-weight:400;line-height:16px;margin-bottom:2px}
.A .fits{font-size:13.5px;line-height:21px;color:var(--ink2);margin:14px 0 20px}
.A .fits a{color:var(--red);font-weight:500}
.A .bbox{background:var(--paper);border-radius:16px;padding:22px 24px 20px}
.A .bbox .pr{display:flex;align-items:baseline;gap:10px}
.A .bbox .pr b{font-size:34px;line-height:40px;font-weight:600;letter-spacing:-.015em}
.A .bbox .pr span{color:var(--muted);font-size:14px}
.A .wh{margin:12px 0 16px;font-size:13.5px;line-height:20px}
.A .wh div{display:flex;justify-content:space-between;padding:6px 0;border-top:1px dashed #DCD5CD;color:var(--ink2)}
.A .wh div:first-child{border-top:0}
.A .wh .ok{color:var(--ok);font-weight:500}.A .wh .ord{color:#9A6A12;font-weight:500}
.A .qrow{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.A .qrow .qty{height:46px;background:#fff}
.A .qrow .qty span{width:42px;height:44px}.A .qrow .qty i{width:34px;font-size:16px}
.A .qrow .tot{text-align:right;font-size:13px;color:var(--muted);line-height:18px}
.A .qrow .tot b{display:block;font-size:20px;line-height:26px;color:var(--ink);font-weight:600}
.A .bbtn{display:grid;grid-template-columns:1.35fr 1fr;gap:10px}
.A .bbtn .btn{height:50px}
.A .dl{margin-top:16px;display:grid;gap:8px;font-size:13.5px;line-height:20px;color:var(--ink2)}
.A .dl div{display:flex;gap:10px;align-items:flex-start}.A .dl .ico{color:var(--muted);flex:none;margin-top:0}
.A .note{margin-top:14px;font-size:12.5px;line-height:18px;color:var(--muted);display:flex;gap:8px}
.A .ptabs{margin-top:44px;border-bottom:1px solid var(--line);display:flex;gap:32px}
.A .ptabs span{height:52px;display:flex;align-items:center;gap:6px;font-size:15px;font-weight:500;color:var(--muted);border-bottom:2px solid transparent}
.A .ptabs span.on{color:var(--ink);border-color:var(--red)}
.A .ptabs em{font-style:normal;font-size:12px;background:var(--paper);border-radius:10px;padding:1px 7px;color:var(--ink2)}
.A .pbody{display:grid;grid-template-columns:1fr 470px;gap:48px;margin-top:32px}
.A h3.h{font:500 22px/30px Literata,serif;margin:0 0 14px}
.A .specs div{display:grid;grid-template-columns:230px 1fr;padding:11px 0;border-top:1px solid var(--line);font-size:14.5px;line-height:21px}
.A .specs div span{color:var(--muted)}
.A .desc{margin-top:28px;color:var(--ink2);font-size:15px;line-height:25px}
.A .compat{border:1px solid var(--line);border-radius:16px;padding:22px 24px}
.A .compat .s{height:42px;border:1px solid var(--line);border-radius:10px;display:flex;align-items:center;gap:8px;padding:0 12px;color:var(--muted);font-size:14px;margin-bottom:16px}
.A .compat h6{margin:12px 0 8px;font-size:13px;font-weight:600;color:var(--ink);display:flex;justify-content:space-between}
.A .compat h6 span{color:var(--muted);font-weight:400}
.A .models{display:flex;flex-wrap:wrap;gap:6px}
.A .models span{height:30px;padding:0 10px;border-radius:8px;background:var(--paper);font-size:13px;display:flex;align-items:center;color:var(--ink2)}
.A .icards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:40px}
.A .icards div{border:1px solid var(--line);border-radius:14px;padding:20px 22px;font-size:13.5px;line-height:21px;color:var(--ink2)}
.A .icards b{display:flex;gap:10px;align-items:center;font-size:15px;color:var(--ink);margin-bottom:8px}
.A .icards b .ico{color:var(--red)}
.A .icards a{display:inline-flex;margin-top:10px;color:var(--red);font-weight:500;gap:4px;align-items:center}
.A .demo{font-size:12px;color:var(--muted);margin-top:10px}
/* мобильная версия */
.A.m{font-size:14.5px;line-height:22px}
.A.m .mw{padding:0 16px}
.A .mh{height:56px;display:flex;align-items:center;justify-content:space-between;padding:0 6px 0 4px;border-bottom:1px solid var(--line);background:#fff}
.A .mh .logo b{font-size:17px;line-height:20px}.A .mh .mono{width:32px;height:32px;font-size:13.5px;border-radius:8px}
.A .ib44{width:44px;height:44px;display:flex;align-items:center;justify-content:center;position:relative;color:var(--ink)}
.A .ib44 .badge{top:4px;left:24px}
.A .msearch{margin:12px 16px 0;height:46px;border:1px solid var(--line);border-radius:10px;display:flex;align-items:center;gap:10px;padding:0 14px;color:var(--muted);font-size:14.5px}
.A .mhero{margin:16px 16px 0;border-radius:14px;overflow:hidden;background:var(--paper)}
.A .mhero img{height:196px;width:100%;object-fit:cover;object-position:68% 60%}
.A .mhero .t{padding:18px 18px 20px}
.A .mhero h1{font:500 27px/33px Literata,serif;margin:0 0 8px;letter-spacing:-.01em}
.A .mhero p{margin:0 0 16px;color:var(--ink2);font-size:14.5px;line-height:21px}
.A .mhero .btn{width:100%;height:48px}
.A .mfinder{margin:12px 16px 0;border:1px solid var(--line);border-radius:14px;padding:16px;display:grid;gap:10px}
.A .mfinder b{font:500 18px/24px Literata,serif}
.A .mfinder .btn{height:48px}
.A .msec{margin:32px 16px 14px;display:flex;justify-content:space-between;align-items:baseline}
.A .msec h2{font:500 22px/28px Literata,serif;margin:0}
.A .msec a{color:var(--red);font-size:14px;font-weight:500}
.A .mcats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:0 16px}
.A .mcats .im{height:96px;border-radius:12px;background:var(--tile);overflow:hidden}.A .mcats img{width:100%;height:100%;object-fit:cover}
.A .mcats b{display:block;font-size:13px;line-height:17px;font-weight:500;margin-top:8px}
.A .mbrands{display:grid;grid-template-columns:repeat(4,1fr);margin:0 16px;border:1px solid var(--line);border-radius:14px;overflow:hidden}
.A .mbrands span{height:56px;display:flex;align-items:center;justify-content:center;border-left:1px solid var(--line);border-top:1px solid var(--line)}
.A .mbrands span:nth-child(4n+1){border-left:0}.A .mbrands span:nth-child(-n+4){border-top:0}
.A .mbrands span.t{font-size:12.5px;color:var(--ink2);font-weight:500}
.A .mgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 16px}
.A .mcard{border:1px solid var(--line);border-radius:14px;overflow:hidden;display:flex;flex-direction:column}
.A .mcard .im{height:124px;display:flex;align-items:center;justify-content:center;padding:14px;position:relative}
.A .mcard .im img{max-height:92px;object-fit:contain}
.A .mcard .fav{top:6px;right:6px;width:36px;height:36px}
.A .mcard .bd{padding:2px 12px 12px;display:flex;flex-direction:column;flex:1}
.A .mcard .for{font-size:12px;line-height:16px;color:var(--muted)}
.A .mcard h3{font-size:14px;line-height:19px;font-weight:500;margin:4px 0 6px;height:57px;overflow:hidden}
.A .mcard .pr{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.A .mcard .pr b{font-size:17px;font-weight:600}
.A .mcard .btn{height:44px;width:100%;font-size:14px;border-radius:10px}
.A .mhelp{margin:32px 16px 0;background:var(--paper);border-radius:14px;padding:20px}
.A .mhelp h3{font:500 21px/27px Literata,serif;margin:0 0 6px}.A .mhelp p{margin:0 0 14px;color:var(--ink2)}
.A .mhelp .btn{width:100%;height:48px}
.A .macc{margin:28px 16px 0;border-top:1px solid var(--line)}
.A .macc div{height:52px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;font-weight:500}
.A .mfoot{padding:20px 16px 100px;font-size:13px;color:var(--muted);line-height:20px}
.A .mfoot b{display:block;font-size:17px;color:var(--ink);margin-bottom:4px}
.A .tabbar{position:absolute;left:0;right:0;bottom:0;height:64px;background:rgba(255,255,255,.97);border-top:1px solid var(--line);display:grid;grid-template-columns:repeat(5,1fr)}
.A .tabbar span{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;font-size:11px;color:var(--muted);position:relative}
.A .tabbar span.on{color:var(--red)}
.A .tabbar .badge{top:6px;left:calc(50% + 4px)}
.A .mtop{height:52px;display:flex;align-items:center;justify-content:space-between;padding:0 4px;border-bottom:1px solid var(--line)}
.A .mtop .r{display:flex}
.A .mgal{height:300px;display:flex;align-items:center;justify-content:center;position:relative;border-bottom:1px solid var(--line)}
.A .mgal img{max-height:210px;max-width:330px;object-fit:contain}
.A .dots{position:absolute;bottom:14px;left:0;right:0;display:flex;justify-content:center;gap:6px}
.A .dots i{width:6px;height:6px;border-radius:3px;background:#D6D0C9}.A .dots i.on{width:18px;background:var(--ink)}
.A .mgal .n{position:absolute;right:14px;bottom:10px;font-size:12px;color:var(--muted)}
.A .mi{padding:16px 16px 0}
.A .mi .k{font-size:12.5px;color:var(--muted)}
.A .mi h1{font:500 22px/28px Literata,serif;margin:6px 0 8px;letter-spacing:-.005em}
.A .mi .meta{font-size:12.5px;line-height:19px}
.A .mfacts{display:flex;gap:6px;margin:12px 0 0;flex-wrap:wrap}
.A .mfacts span{height:30px;padding:0 10px;border-radius:15px;background:var(--paper);font-size:12.5px;display:flex;align-items:center;gap:6px;color:var(--ink2)}
.A .mbuy{margin:14px 16px 0;background:var(--paper);border-radius:14px;padding:16px}
.A .mbuy .pr{display:flex;align-items:baseline;justify-content:space-between}
.A .mbuy .pr b{font-size:28px;line-height:34px;font-weight:600;letter-spacing:-.01em}
.A .mbuy .q{display:flex;justify-content:space-between;align-items:center;margin:12px 0}
.A .mbuy .qty{height:46px;background:#fff}.A .mbuy .qty span{width:44px;height:44px}.A .mbuy .qty i{width:30px;font-size:16px}
.A .mbuy .tot{text-align:right;font-size:12px;color:var(--muted);line-height:16px}.A .mbuy .tot b{display:block;color:var(--ink);font-size:18px;line-height:24px}
.A .mbuy .btn{width:100%;height:50px}
.A .mbuy .btn+.btn{margin-top:8px}
.A .mstick{overflow:hidden;position:sticky;top:0;background:#fff;border-bottom:1px solid var(--line);display:flex;gap:22px;padding:0 16px;height:46px;align-items:center;font-size:14px;color:var(--muted);white-space:nowrap}
.A .mstick span.on{color:var(--ink);font-weight:600;box-shadow:inset 0 -2px 0 var(--red);height:46px;display:flex;align-items:center}
.A .msp{padding:4px 16px}
.A .msp h3{font:500 20px/26px Literata,serif;margin:18px 0 8px}
.A .msp .specs div{grid-template-columns:150px 1fr;font-size:13.5px;line-height:19px;padding:10px 0}
.A .sbar{position:absolute;left:0;right:0;bottom:0;background:#fff;border-top:1px solid var(--line);box-shadow:0 -8px 24px rgba(40,30,20,.08);padding:10px 16px 14px;display:grid;grid-template-columns:auto auto 1fr;gap:10px;align-items:center}
.A .sbar .p{font-size:12px;line-height:15px;color:var(--muted)}.A .sbar .p b{display:block;font-size:18px;line-height:22px;color:var(--ink)}
.A .sbar .qty{height:44px}.A .sbar .qty span{width:36px;height:42px}.A .sbar .qty i{width:22px}
.A .sbar .btn{height:48px;padding:0 10px}
`;

const logo = (sub = 'картриджи · бумага · экраны') => `<a class="logo" href="A-home.html"><span class="mono">SR</span><span><b>Sakura Russia</b><small>${sub}</small></span></a>`;

function header() {
  return `<div class="util"><div class="wrap"><div class="l">${icon('pin', 15)}Москва · Самовывоз: ${STORE.addressShort}</div><div class="r"><a>Доставка и оплата</a><a>Гарантия и возврат</a><a>О компании</a><a>Контакты</a><a>Помощь</a></div></div></div>
<header class="hdr"><div class="wrap">${logo()}
<div class="search">${icon('search', 19)}<span>Модель принтера, номер картриджа или название</span><b class="go">Найти</b></div>
<div class="phone"><b>${STORE.phone}</b><small>${STORE.hours}</small></div>
<div class="acts"><a class="act">${icon('heart', 22)}Избранное</a><a class="act">${icon('compare', 22)}Сравнение</a><a class="act">${icon('cart', 22)}<span class="badge">2</span>850 ₽</a><a class="act">${icon('user', 22)}Войти</a></div>
</div></header>
<nav class="nav"><div class="wrap"><a class="catbtn">${icon('menu', 18)}Каталог</a><a class="l" href="A-catalog.html">Лазерные картриджи</a><a class="l">Струйные картриджи</a><a class="l">Экраны</a><a class="l">Фотобумага</a><a class="l">Широкоформатная бумага</a><a class="l">Крепления</a><a class="find">${icon('printer', 18)}Подбор по принтеру</a></div></nav>`;
}

function footer() {
  return `<footer class="ftr"><div class="bgimg"><img src="${asset('concept05/footer-fullwidth-alpha.png')}" alt=""></div><div class="wrap">
<div class="fcols"><div>${logo()}<p>Интернет-магазин расходных материалов для печати и проекционного оборудования. Розница по России.</p></div>
<div><h4>Каталог</h4><a>Лазерные картриджи</a><a>Струйные картриджи</a><a>Проекционные экраны</a><a>Фотобумага</a><a>Широкоформатная бумага</a><a>Крепления</a></div>
<div><h4>Покупателям</h4><a>Подбор картриджа</a><a>Доставка и оплата</a><a>Гарантия и возврат</a><a>Помощь и вопросы</a><a>Личный кабинет</a></div>
<div><h4>Компания</h4><a>О компании</a><a>Контакты</a><a>Реквизиты</a><a>Политика конфиденциальности</a></div>
<div><h4>Контакты</h4><a><b>${STORE.phone}</b></a><a>${STORE.email}</a><p>${STORE.address}<br>${STORE.hours}</p></div></div>
<div class="fbot"><span>© 2026 Sakura Russia. Реквизиты продавца будут указаны после согласования.</span><div><span>Демонстрационный макет: цены, остатки и сроки условные</span></div></div></div></footer>`;
}

function card(p, { wide = false } = {}) {
  const st = p.stock;
  const btn = st === 'in' ? `<a class="btn s">${icon('cart', 17)}В корзину</a>` : st === 'order' ? `<a class="btn s ghost">Заказать</a>` : `<a class="btn s line">Сообщить о поступлении</a>`;
  return `<article class="card"><a class="im" href="A-product.html"><img src="${asset(`products/${p.img}.jpg`)}" alt="${esc(p.name)}"><span class="fav">${icon('heart', 18)}</span></a>
<div class="bd"><div class="for">Для ${p.for} · аналог ${p.oem.split(',')[0]}</div><h3>${esc(p.name)}</h3>
<div class="sp">${num(p.yield)} стр. <span class="dot" style="background:${colorDot[p.color]}"></span>${p.color}</div>
<div class="pr"><b>${rub(p.price)}</b><span class="st ${st}">${stockLabel[st]}</span></div>
<div class="buy">${st === 'out' ? '' : `<span class="qty"><span>${icon('minus', 16)}</span><i>1</i><span>${icon('plus', 16)}</span></span>`}${btn}</div></div></article>`;
}

function brandStrip() {
  return `<div class="brands"><div class="h">Картриджи для принтеров<small>Выберите производителя</small></div>${BRANDS.map(b => { const s = logoSize(b, 1300, 26); return `<a class="b"><img src="${asset(b.file)}" alt="${b.name}" style="height:${s.h}px;width:${s.w}px"></a>`; }).join('')}<a class="bmore">Ещё 6 ${icon('right', 16)}</a></div>`;
}

export function home() {
  const body = `<div class="screen A" data-name="A / 01 Главная / Desktop 1440" style="width:1440px">${header()}
<main class="wrap">
<section class="hero"><div class="txt"><div class="eyebrow">Картриджи, бумага и экраны</div><h1>Картриджи для вашего принтера и всё для печати</h1><p>Подберите картридж по модели принтера или номеру оригинала. Доставка по России, самовывоз в Москве.</p><div class="cta"><a class="btn">${icon('printer', 19)}Подобрать картридж</a><a class="btn ghost">Открыть каталог</a></div></div>
<div class="ph"><img src="${asset('generated/web/hero-a.jpg')}" alt="Лазерный принтер, картридж и бумага"></div></section>
<section class="finder"><div class="t"><b>Подбор по принтеру</b><small>Производитель и модель</small></div>
<div class="field"><div class="v"><label>Производитель</label>HP</div>${icon('down', 18)}</div>
<div class="field"><div class="v"><label>Модель принтера</label><span class="ph2">Например, LaserJet P1102</span></div>${icon('search', 18)}</div>
<a class="btn">Показать картриджи</a></section>
<div class="sechead"><h2 class="sec">Каталог</h2><a class="link">Все категории ${icon('arrow', 17)}</a></div>
<section class="cats">${CATEGORIES.map(c => `<a class="cat" ${c.id === 'laser' ? 'href="A-catalog.html"' : ''}><div class="im"><img src="${asset(c.img)}" alt=""></div><b>${c.name}</b><small>${c.note}</small></a>`).join('')}</section>
${brandStrip()}
<div class="sechead"><h2 class="sec">Популярные картриджи</h2><div class="tabs"><span class="pill on">Лазерные</span><span class="pill">Цветные</span><span class="pill">Струйные</span><span class="pill">Для Kyocera</span></div></div>
<section class="grid4">${['ce285a', 'tk1170', 'w1106a', 'cb540a'].map(id => card(P[id])).join('')}</section>
<section class="help"><div><h3>Не нашли свой картридж?</h3><p>Пришлите модель принтера или номер старого картриджа. Менеджер проверит совместимость и предложит замену.</p><div style="display:flex;gap:12px"><a class="btn">Отправить запрос</a><a class="btn ghost">${icon('phone', 18)}${STORE.phone}</a></div></div>
<div class="steps"><div><b>01</b>Найдите модель на корпусе или наклейке принтера</div><div><b>02</b>Введите модель или номер оригинального картриджа в поиске</div><div><b>03</b>Сверьте список совместимых устройств в карточке товара</div></div></section>
<section class="svc"><div>${icon('truck', 24)}<span><b>Доставка по России</b>СДЭК до двери или пункта выдачи</span></div><div>${icon('pin', 24)}<span><b>Самовывоз в Москве</b>${STORE.addressShort}, ${STORE.hours.toLowerCase()}</span></div><div>${icon('card', 24)}<span><b>Оплата картой и СБП</b>Онлайн при оформлении заказа</span></div><div>${icon('return', 24)}<span><b>Гарантия и возврат</b>Порядок описан на отдельной странице</span></div></section>
</main>${footer()}</div>`;
  return page({ title: 'A / Главная', families: FAM, css: CSS, body, width: 1440 });
}

export function catalog() {
  const ids = ['ce285a', 'cb435a', 'cb436a', 'w1106a', 'cb540a', 'cb541a', 'cb542a', 'cb543a', 'c7115a'];
  const chk = (t, n, on) => `<div class="chk ${on ? 'on' : ''}"><i>${on ? icon('check', 13, 2.4) : ''}</i>${t}<em>${n}</em></div>`;
  const body = `<div class="screen A" data-name="A / 02 Каталог / Desktop 1440" style="width:1440px">${header()}
<main class="wrap"><div class="crumbs"><a>Главная</a>${icon('right', 14)}<a>Каталог</a>${icon('right', 14)}<a>Лазерные картриджи</a>${icon('right', 14)}<span style="color:var(--ink2)">HP</span></div>
<div class="ttl"><h1>Лазерные картриджи для HP</h1><span>12 товаров в демокаталоге</span></div>
<div class="subcats"><span class="pill on">Все</span><span class="pill">Чёрно-белая печать</span><span class="pill">Цветная печать</span><span class="pill">Повышенный ресурс</span><span class="pill">Для МФУ</span></div>
<div class="cl"><aside class="side">
<div class="grp"><h5>Наличие</h5>${chk('В наличии', 10, true)}${chk('Под заказ', 1)}${chk('Показать отсутствующие', 1)}</div>
<div class="grp"><h5>Производитель принтера ${icon('down', 16)}</h5>${chk('HP', 12, true)}${chk('Canon', 6)}${chk('Kyocera', 2)}${chk('Samsung', 0)}<a class="link" style="font-size:13.5px;margin-top:6px">Все производители</a></div>
<div class="grp"><h5>Серия принтера ${icon('down', 16)}</h5>${chk('LaserJet P1000–P1600', 4)}${chk('LaserJet 1000–3300', 1)}${chk('Color LaserJet CP1215–CP1518', 4)}${chk('Laser 107–137', 1)}</div>
<div class="grp"><h5>Цвет печати</h5><div class="sw">${['Чёрный', 'Голубой', 'Пурпурный', 'Жёлтый'].map(c => `<span><i class="dot" style="background:${colorDot[c]}"></i>${c}</span>`).join('')}</div></div>
<div class="grp"><h5>Ресурс, страниц</h5><div class="sw"><span>до 2 000</span><span>2 000–5 000</span><span>от 5 000</span></div></div>
<div class="grp"><h5>Цена, ₽</h5><div class="range"><span>от 413</span><span>до 5 693</span></div><div class="bar"><i></i></div></div>
<a class="btn" style="width:100%;margin-top:6px">Показать 10 товаров</a><a class="btn line" style="width:100%;margin-top:8px;height:42px">Сбросить фильтры</a>
</aside><section>
<div class="tb"><div class="chips"><span class="chip">HP ${icon('close', 14)}</span><span class="chip">В наличии ${icon('close', 14)}</span><a class="link" style="font-size:13.5px;margin-left:6px">Сбросить</a></div>
<div class="sort">Сортировка <span class="sel">По популярности ${icon('down', 16)}</span><span class="vt"><span class="on">${icon('grid', 18)}</span><span>${icon('list', 18)}</span></span></div></div>
<div class="grid3">${ids.map(id => card(P[id])).join('')}</div>
<div class="more"><a class="btn ghost">Показать ещё 3</a><div class="pg"><span class="on">1</span><span>2</span></div></div>
</section></div></main>${footer()}</div>`;
  return page({ title: 'A / Каталог', families: FAM, css: CSS, body, width: 1440 });
}

export function product() {
  const p = P.ce285a;
  const body = `<div class="screen A" data-name="A / 03 Товар CE285A / Desktop 1440" style="width:1440px">${header()}
<main class="wrap"><div class="crumbs"><a>Главная</a>${icon('right', 14)}<a>Лазерные картриджи</a>${icon('right', 14)}<a>HP</a>${icon('right', 14)}<span style="color:var(--ink2)">CE285A</span></div>
<section class="pp"><div class="thumbs"><span class="on"><img src="${asset('products/ce285a.jpg')}" alt=""></span><span><img src="${asset('products/ce285a-label.jpg')}" alt=""></span><span><img src="${asset('products/ce285a-end.jpg')}" alt=""></span></div>
<div class="main"><img src="${asset('products/ce285a.jpg')}" alt="Упаковка картриджа SAKURA CE285A"><span class="cap">Фото упаковки · источник: sakurarussia.ru</span><span class="zoom">${icon('search', 15)}Увеличить</span></div>
<div class="pdesc"><h3 class="h">Описание</h3><p>Совместимый картридж SAKURA для лазерных принтеров HP и Canon. Чёрная печать, ресурс до 2 000 страниц при 5% заполнении. Перед заказом проверьте точную модель принтера в списке совместимости.</p><ul><li>${icon('check', 17)}Новый картридж в заводской упаковке</li><li>${icon('check', 17)}Аналоги: HP 85A, 35A, 36A, Canon 725</li><li>${icon('check', 17)}Для 17 моделей HP и Canon</li><li>${icon('check', 17)}Вес 0,806 кг</li></ul></div>
<div class="info"><div class="top"><span>SAKURA · совместимый картридж</span><div class="ia"><span class="ib">${icon('heart', 18)}</span><span class="ib">${icon('compare', 18)}</span><span class="ib">${icon('share', 18)}</span></div></div>
<h1>${esc(p.name)}</h1>
<div class="meta"><span>Артикул:</span> ${p.sku} &nbsp;${icon('copy', 14)}<br><span>Аналог оригинала:</span> ${p.oem}</div>
<div class="facts"><div><small>Ресурс</small>2 000 страниц</div><div><small>Цвет печати</small><span class="dot" style="background:#1d1d1f"></span> Чёрный</div><div><small>Технология</small>Лазерная</div><div><small>Совместимость</small>17 моделей HP и Canon</div></div>
<div class="fits">Подходит для HP LaserJet P1102, P1102W, P1005, P1006, P1505 и ещё 12 моделей. <a>Весь список</a></div>
<div class="bbox"><div class="pr"><b>${rub(p.price)}</b><span>за 1 шт.</span></div>
<div class="wh"><div><span>Москва, ${STORE.addressShort}</span><span class="ok">В наличии</span></div><div><span>Склад поставщика</span><span class="ord">Под заказ</span></div></div>
<div class="qrow"><span class="qty"><span>${icon('minus', 18)}</span><i>2</i><span>${icon('plus', 18)}</span></span><div class="tot">Итого за 2 шт.<b>850 ₽</b></div></div>
<div class="bbtn"><a class="btn">${icon('cart', 19)}В корзину</a><a class="btn ghost">Купить в 1 клик</a></div>
<div class="dl"><div>${icon('truck', 18)}<span>Доставка СДЭК: стоимость и срок рассчитываются при оформлении</span></div><div>${icon('pin', 18)}<span>Самовывоз: ${STORE.addressShort}, ${STORE.hours.toLowerCase()}</span></div></div></div>
<div class="note">${icon('info', 16)}Перед покупкой сверьте модель принтера со списком совместимости.</div>
</div></section>
<nav class="ptabs"><span>Описание</span><span class="on">Характеристики</span><span>Совместимость <em>17</em></span><span>Доставка и оплата</span><span>Гарантия и возврат</span><span>Документы</span></nav>
<section class="pbody"><div><h3 class="h">Характеристики</h3><div class="specs">${CE285A_SPECS.map(([k, v]) => `<div><span>${k}</span>${v}</div>`).join('')}</div>
</div>
<div class="compat"><h3 class="h">Совместимость</h3><div class="s">${icon('search', 17)}Найти модель принтера</div>${Object.entries(CE285A_COMPAT).map(([s, ms]) => `<h6>${s}<span>${ms.length}</span></h6><div class="models">${ms.map(m => `<span>${m}</span>`).join('')}</div>`).join('')}<div class="demo">Список приведён по названию товара в текущем магазине; требует проверки.</div></div></section>
<section class="icards"><div><b>${icon('truck', 20)}Доставка и оплата</b>СДЭК до двери или пункта выдачи, самовывоз в Москве. Оплата картой или через СБП при оформлении.<br><a>Подробнее ${icon('arrow', 15)}</a></div>
<div><b>${icon('shield', 20)}Гарантия и возврат</b>Условия гарантии и порядок возврата описаны на отдельной странице. Срок гарантии подтверждает продавец.<br><a>Подробнее ${icon('arrow', 15)}</a></div>
<div><b>${icon('file', 20)}Документы</b>Инструкция по установке и документы товара появятся после загрузки в каталог.<br><a>Все документы ${icon('arrow', 15)}</a></div></section>
<div class="sechead"><h2 class="sec">Похожие картриджи</h2><a class="link">Все для HP LaserJet P1102 ${icon('arrow', 17)}</a></div>
<section class="grid4" style="margin-bottom:64px">${['cb435a', 'cb436a', 'w1106a', 'c7115a'].map(id => card(P[id])).join('')}</section>
</main>${footer()}</div>`;
  return page({ title: 'A / Товар', families: FAM, css: CSS, body, width: 1440 });
}

const mLogo = () => `<a class="logo" href="A-m-home.html"><span class="mono">SR</span><span><b>Sakura Russia</b></span></a>`;

export function mHome() {
  const mcard = p => `<article class="mcard"><a class="im" href="A-m-product.html"><img src="${asset(`products/${p.img}.jpg`)}" alt=""><span class="fav">${icon('heart', 17)}</span></a><div class="bd"><div class="for">${num(p.yield)} стр. · ${p.color}</div><h3>${esc(p.name)}</h3><div class="pr"><b>${rub(p.price)}</b><span class="st">В наличии</span></div><a class="btn">${icon('cart', 17)}В корзину</a></div></article>`;
  const body = `<div class="screen A m" data-name="A / 04 Главная / Mobile 390" style="width:390px">
<div class="mh"><span class="ib44">${icon('menu', 22)}</span>${mLogo()}<span class="ib44">${icon('cart', 22)}<span class="badge">2</span></span></div>
<div class="msearch">${icon('search', 19)}Модель принтера или картриджа</div>
<section class="mhero"><img src="${asset('generated/web/hero-a.jpg')}" alt=""><div class="t"><h1>Картриджи для вашего принтера</h1><p>Подбор по модели принтера или номеру оригинала. Доставка по России.</p><a class="btn">${icon('printer', 19)}Подобрать картридж</a></div></section>
<div class="msec"><h2>Каталог</h2><a>Все</a></div>
<section class="mcats">${CATEGORIES.map(c => `<a><div class="im"><img src="${asset(c.img)}" alt=""></div><b>${c.name}</b></a>`).join('')}</section>
<div class="msec"><h2>Для принтеров</h2><a>Все бренды</a></div>
<section class="mbrands">${BRANDS.map(b => { const s = logoSize(b, 820, 24); return `<span><img src="${asset(b.file)}" alt="${b.name}" style="height:${s.h}px;width:${s.w}px"></span>`; }).join('')}<span class="t">Ещё 6</span></section>
<div class="msec"><h2>Популярные</h2><a>Смотреть все</a></div>
<section class="mgrid">${['ce285a', 'tk1170', 'w1106a', 'cb435a'].map(id => mcard(P[id])).join('')}</section>
<section class="mhelp"><h3>Не нашли картридж?</h3><p>Пришлите модель принтера, менеджер проверит совместимость.</p><a class="btn ghost">Отправить запрос</a></section>
<div class="macc"><div>Каталог ${icon('down', 18)}</div><div>Покупателям ${icon('down', 18)}</div><div>Компания ${icon('down', 18)}</div></div>
<div class="mfoot"><b>${STORE.phone}</b>${STORE.hours}<br>${STORE.address}<br><br>Демонстрационный макет: цены и остатки условные</div>
<nav class="tabbar"><span class="on">${icon('home', 22)}Главная</span><span>${icon('menu', 22)}Каталог</span><span>${icon('heart', 22)}Избранное</span><span>${icon('cart', 22)}<b class="badge">2</b>Корзина</span><span>${icon('user', 22)}Профиль</span></nav></div>`;
  return page({ title: 'A / Mobile главная', families: FAM, css: CSS, body, width: 390 });
}

export function mProduct() {
  const p = P.ce285a;
  const body = `<div class="screen A m" data-name="A / 05 Товар: первый экран / Mobile 390" style="width:390px;height:844px">
<div class="mtop"><span class="ib44">${icon('left', 22)}</span><div class="r"><span class="ib44">${icon('heart', 21)}</span><span class="ib44">${icon('share', 21)}</span><span class="ib44">${icon('cart', 21)}<span class="badge">2</span></span></div></div>
<div class="mgal"><img src="${asset('products/ce285a.jpg')}" alt=""><div class="dots"><i class="on"></i><i></i><i></i></div><span class="n">1 / 3</span></div>
<div class="mi"><div class="k">SAKURA · совместимый картридж</div><h1>${esc(p.name)}</h1><div class="meta"><span>Артикул</span> ${p.sku}<br><span>Аналог:</span> ${p.oem}</div>
<div class="mfacts"><span>2 000 стр.</span><span><i class="dot" style="background:#1d1d1f"></i>Чёрный</span><span>Лазерная</span><span>17 моделей ${icon('right', 13)}</span></div></div>
<section class="mbuy"><div class="pr"><b>${rub(p.price)}</b><span class="st">В наличии · Москва</span></div>
<div class="q"><span class="qty"><span>${icon('minus', 18)}</span><i>2</i><span>${icon('plus', 18)}</span></span><div class="tot">Итого за 2 шт.<b>850 ₽</b></div></div>
<a class="btn">${icon('cart', 19)}В корзину</a><a class="btn ghost">Купить в 1 клик</a></section></div>`;
  return page({ title: 'A / Mobile товар', families: FAM, css: CSS, body, width: 390 });
}

export function mProductScroll() {
  const body = `<div class="screen A m" data-name="A / 06 Товар: прокрутка, панель покупки / Mobile 390" style="width:390px;height:844px">
<div class="mstick"><span>Описание</span><span class="on">Характеристики</span><span>Совместимость 17</span><span>Доставка</span></div>
<div class="msp"><h3>Характеристики</h3><div class="specs">${CE285A_SPECS.slice(0, 6).map(([k, v]) => `<div><span>${k}</span>${v}</div>`).join('')}</div>
<h3>Совместимость</h3><div class="compat" style="padding:14px"><div class="s">${icon('search', 17)}Найти модель принтера</div><h6>HP LaserJet<span>12</span></h6><div class="models">${CE285A_COMPAT['HP LaserJet'].slice(0, 8).map(m => `<span>${m}</span>`).join('')}<span>+4</span></div><h6>Canon i-SENSYS<span>5</span></h6><div class="models">${CE285A_COMPAT['Canon i-SENSYS'].map(m => `<span>${m}</span>`).join('')}</div></div></div>
<div class="sbar"><div class="p">2 × 425 ₽<b>850 ₽</b></div><span class="qty"><span>${icon('minus', 16)}</span><i>2</i><span>${icon('plus', 16)}</span></span><a class="btn">В корзину</a></div></div>`;
  return page({ title: 'A / Mobile товар, прокрутка', families: FAM, css: CSS, body, width: 390 });
}
