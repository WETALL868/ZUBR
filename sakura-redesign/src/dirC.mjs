// Направление C «Мой принтер»: сценарий строится вокруг устройства покупателя. Manrope, мягкие поверхности, фильтры-чипы.
import { asset, icon, rub, num, esc, STORE, P, CATEGORIES, BRANDS, logoSize, CE285A_COMPAT, CE285A_SPECS, stockLabel, colorDot, page } from './common.mjs';

const FAM = ['Manrope'];
const CSS = `
.C{--ink:#1C1B1F;--ink2:#46444B;--muted:#77747C;--line:#E9E6E3;--surf:#F7F6F4;--tile:#F0EDE9;--red:#C5302D;--redd:#A82623;--reds:#FCF2F1;--redl:#F3D6D3;--ok:#1F7A4A;--okbg:#EAF5EE;--warn:#8A5B00;
  background:#fff;color:var(--ink);font:500 15px/23px Manrope,sans-serif}
.C .wrap{width:1280px;margin:0 auto}
.C .hdr .wrap{display:flex;align-items:center;gap:24px;height:72px}
.C .logo{display:flex;align-items:center;gap:10px;flex:none}
.C .mk{width:36px;height:36px;border-radius:10px;background:var(--red);position:relative;flex:none}
.C .mk:before{content:"";position:absolute;left:8px;right:8px;top:12px;height:4px;border-radius:2px;background:#fff}
.C .mk:after{content:"";position:absolute;left:12px;right:12px;top:19px;height:9px;border-radius:2px;background:#fff;opacity:.85}
.C .logo b{font:800 19px/22px Manrope,sans-serif;letter-spacing:-.02em}
.C .logo small{display:block;font-size:11.5px;line-height:15px;color:var(--muted);font-weight:500}
.C .search{flex:1;height:48px;border-radius:12px;background:var(--surf);display:flex;align-items:center;gap:10px;padding:0 16px;color:var(--muted);font-size:14.5px}
.C .mine{height:48px;border-radius:12px;background:var(--reds);border:1px solid var(--redl);display:flex;align-items:center;gap:10px;padding:0 12px 0 14px;color:var(--ink);flex:none}
.C .mine .ico{color:var(--red)}
.C .mine small{display:block;font-size:11px;line-height:13px;color:var(--muted);font-weight:600}
.C .mine b{display:block;font-size:14px;line-height:18px;font-weight:700}
.C .icons{display:flex;gap:4px}
.C .i48{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;position:relative;color:var(--ink2)}
.C .i48 .n{position:absolute;top:5px;right:4px;min-width:18px;height:18px;border-radius:9px;background:var(--red);color:#fff;font-size:11px;font-weight:700;line-height:18px;text-align:center;padding:0 5px}
.C .nav{border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.C .nav .wrap{display:flex;align-items:center;gap:6px;height:52px}
.C .nav a{height:36px;padding:0 12px;border-radius:18px;display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:var(--ink2);white-space:nowrap;flex:none}
.C .nav a.all{background:var(--ink);color:#fff;padding:0 16px 0 12px}
.C .nav .sp{margin-left:auto;color:var(--muted);font-weight:600;white-space:nowrap;font-size:14px}
.C .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:48px;padding:0 22px;border-radius:12px;background:var(--red);color:#fff;font-weight:700;font-size:15px;border:0;white-space:nowrap}
.C .btn.s{height:42px;padding:0 16px;font-size:14px;border-radius:11px}
.C .btn.soft{background:var(--reds);color:var(--redd)}
.C .btn.out{background:#fff;color:var(--ink);border:1px solid #D9D5D1}
.C .lnk{color:var(--red);font-weight:700;display:inline-flex;align-items:center;gap:6px;font-size:14.5px}
.C .chip{height:36px;padding:0 14px;border-radius:18px;border:1px solid var(--line);display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:600;color:var(--ink2);background:#fff;white-space:nowrap}
.C .chip.on{background:var(--ink);border-color:var(--ink);color:#fff}
.C .chip.dd{padding-right:10px}
.C .tag{display:inline-flex;align-items:center;gap:6px;height:26px;padding:0 10px;border-radius:13px;font-size:12px;font-weight:700}
.C .tag.ok{background:var(--okbg);color:var(--ok)}.C .tag.order{background:#FBF1DE;color:var(--warn)}.C .tag.out{background:var(--surf);color:var(--muted)}
.C h2{font:700 26px/34px Manrope,sans-serif;letter-spacing:-.02em;margin:0}
.C .sh{display:flex;align-items:flex-end;justify-content:space-between;margin:52px 0 18px}
.C .sh .l small{display:block;font-size:14px;color:var(--muted);font-weight:500;margin-top:2px}
.C .heroC{background:var(--reds)}
.C .heroC .wrap{display:grid;grid-template-columns:1fr 420px;gap:48px;padding:40px 0 44px;align-items:center}
.C .eb{display:inline-flex;align-items:center;gap:8px;height:30px;padding:0 12px;border-radius:15px;background:#fff;color:var(--red);font-size:13px;font-weight:700}
.C .heroC h1{font:800 42px/50px Manrope,sans-serif;letter-spacing:-.03em;margin:16px 0 22px;max-width:700px}
.C .fcard{background:#fff;border-radius:18px;box-shadow:0 12px 32px rgba(120,40,30,.08);padding:10px;display:grid;grid-template-columns:1fr 1.25fr auto;gap:8px}
.C .step{height:64px;border-radius:12px;background:var(--surf);display:flex;align-items:center;gap:12px;padding:0 14px}
.C .step .num{width:28px;height:28px;border-radius:14px;background:var(--ink);color:#fff;font-size:13px;font-weight:800;display:flex;align-items:center;justify-content:center;flex:none}
.C .step .num.d{background:var(--ok)}
.C .step .v{flex:1}.C .step label{display:block;font-size:12px;line-height:15px;color:var(--muted);font-weight:600}.C .step b{display:block;font-size:15.5px;line-height:21px}
.C .fcard .btn{height:64px;padding:0 24px;border-radius:12px}
.C .pop{display:flex;align-items:center;gap:8px;margin-top:16px;flex-wrap:wrap;font-size:13.5px;color:var(--muted)}
.C .pop .chip{border-color:var(--redl);height:32px}
.C .how{margin-top:14px;font-size:13.5px;color:var(--ink2);display:flex;gap:8px;align-items:center}
.C .dev{background:#fff;border-radius:20px;padding:20px 22px 20px;box-shadow:0 12px 32px rgba(120,40,30,.06)}
.C .dev .top{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;color:var(--muted);font-weight:700;letter-spacing:.02em}
.C .dev img{width:100%;height:190px;object-fit:contain;margin:6px 0 4px}
.C .dev b{display:block;font-size:19px;line-height:25px;font-weight:800;letter-spacing:-.01em}
.C .dev .r{display:flex;gap:8px;margin-top:12px}
.C .dev .r span{flex:1;border-radius:12px;background:var(--surf);padding:10px 12px;font-size:12.5px;line-height:17px;color:var(--muted)}
.C .dev .r span b{font-size:14px;line-height:20px;color:var(--ink)}
.C .brandsC{display:grid;grid-template-columns:repeat(7,1fr) 128px;border:1px solid var(--line);border-radius:16px;overflow:hidden;margin-top:28px}
.C .brandsC a{height:68px;display:flex;align-items:center;justify-content:center;border-left:1px solid var(--line)}
.C .brandsC a:first-child{border-left:0}
.C .brandsC a.on{background:var(--reds);box-shadow:inset 0 -3px 0 var(--red)}
.C .brandsC a.more{font-size:13.5px;font-weight:700;color:var(--ink2);gap:4px}
.C .fitrow{display:grid;grid-template-columns:340px 1fr 1fr;gap:16px}
.C .fitnote{border-radius:16px;background:var(--surf);padding:22px 24px;display:flex;flex-direction:column}
.C .fitnote b{font-size:18px;line-height:25px;font-weight:800}
.C .fitnote p{margin:8px 0 16px;color:var(--ink2);font-size:14px;line-height:21px}
.C .fitnote .lnk{margin-top:auto}
.C .hcard{border:1px solid var(--line);border-radius:16px;padding:14px;display:grid;grid-template-columns:150px 1fr;gap:16px;align-items:center}
.C .hcard .im{height:120px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center}
.C .hcard .im.noimg{flex-direction:column;gap:6px;background:var(--surf);color:#A9A5A0;font-size:11.5px;line-height:15px;text-align:center;padding:0 14px}
.C .hcard .im img{max-height:84px;max-width:140px;object-fit:contain}
.C .hcard h3{margin:6px 0 4px;font-size:15px;line-height:21px;font-weight:700}
.C .hcard .m{font-size:13px;color:var(--muted)}
.C .hcard .b{display:flex;align-items:center;justify-content:space-between;margin-top:10px}
.C .hcard .b b{font-size:20px;font-weight:800}
.C .catsC{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.C .catsC a{height:96px;border:1px solid var(--line);border-radius:16px;display:grid;grid-template-columns:80px 1fr 20px;gap:14px;align-items:center;padding:8px 16px 8px 8px}
.C .catsC .im{width:80px;height:80px;border-radius:12px;background:var(--tile);overflow:hidden}.C .catsC img{width:100%;height:100%;object-fit:cover}
.C .catsC b{display:block;font-size:15px;line-height:20px;font-weight:700}.C .catsC small{display:block;font-size:13px;line-height:18px;color:var(--muted);font-weight:500;margin-top:2px}
.C .catsC .ico{color:var(--muted)}
.C .g4{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.C .pc{border:1px solid var(--line);border-radius:16px;padding:10px;display:flex;flex-direction:column}
.C .pc .im{height:150px;border-radius:12px;background:var(--surf);display:flex;align-items:center;justify-content:center;position:relative}
.C .pc .im img{max-height:96px;max-width:190px;object-fit:contain;mix-blend-mode:multiply}
.C .pc .fv{position:absolute;top:8px;right:8px;width:36px;height:36px;border-radius:18px;background:#fff;display:flex;align-items:center;justify-content:center;color:var(--ink2)}
.C .pc .tg{position:absolute;left:8px;top:8px}
.C .pc .bd{padding:12px 6px 4px;display:flex;flex-direction:column;flex:1}
.C .pc h3{margin:0;font-size:15px;line-height:21px;font-weight:700;height:42px;overflow:hidden}
.C .pc .fit{display:flex;gap:6px;align-items:flex-start;font-size:12.5px;line-height:17px;color:var(--muted);margin:6px 0 0;height:34px;overflow:hidden}
.C .pc .fit .ico{flex:none;color:var(--ink2);margin-top:0}
.C .pc .sp{display:flex;gap:6px;margin-top:10px}
.C .pc .sp span{height:26px;padding:0 9px;border-radius:13px;background:var(--surf);font-size:12px;font-weight:600;display:flex;align-items:center;gap:5px;color:var(--ink2)}
.C .dot{width:9px;height:9px;border-radius:5px;display:inline-block}
.C .pc .pr{display:flex;align-items:center;justify-content:space-between;margin:12px 0 10px}
.C .pc .pr b{font-size:20px;line-height:26px;font-weight:800;letter-spacing:-.01em}
.C .pc .buy{display:flex;gap:8px;margin-top:auto}
.C .qt{height:42px;border-radius:11px;background:var(--surf);display:flex;align-items:center;flex:none}
.C .qt span{width:34px;height:42px;display:flex;align-items:center;justify-content:center;color:var(--ink2)}
.C .qt i{font-style:normal;width:24px;text-align:center;font-weight:700}
.C .pc .buy .btn{flex:1}
.C .ask{margin:56px 0 0;border-radius:20px;background:var(--surf);padding:28px 32px;display:grid;grid-template-columns:1fr 520px;gap:40px;align-items:center}
.C .ask b{font-size:22px;line-height:30px;font-weight:800;letter-spacing:-.01em}
.C .ask p{margin:6px 0 0;color:var(--ink2);font-size:14.5px}
.C .ask .f{display:grid;grid-template-columns:1fr 1fr auto;gap:8px}
.C .inp{height:48px;border-radius:12px;background:#fff;border:1px solid var(--line);display:flex;align-items:center;padding:0 14px;color:var(--muted);font-size:14.5px;gap:8px}
.C .ftr{margin-top:64px;border-top:1px solid var(--line)}
.C .fg{display:grid;grid-template-columns:320px repeat(3,1fr) 260px;gap:32px;padding:40px 0 32px}
.C .fg h4{margin:0 0 12px;font-size:14px;font-weight:800}
.C .fg a{display:block;font-size:14px;line-height:28px;color:var(--ink2)}
.C .fg p{margin:12px 0 0;font-size:13.5px;line-height:21px;color:var(--muted)}
.C .fb{border-top:1px solid var(--line);height:56px;display:flex;align-items:center;justify-content:space-between;font-size:12.5px;color:var(--muted)}
.C .crumbs{display:flex;gap:8px;align-items:center;font-size:13px;color:var(--muted);margin:20px 0 12px;font-weight:600}
/* каталог */
.C .ctx{display:flex;align-items:center;gap:14px;border-radius:16px;background:var(--reds);border:1px solid var(--redl);padding:12px 12px 12px 18px;margin-bottom:22px}
.C .ctx .ico{color:var(--red)}
.C .ctx .t{flex:1;font-size:14.5px}.C .ctx .t b{font-weight:800}
.C .ctx .sel{height:44px;border-radius:11px;background:#fff;display:flex;align-items:center;gap:10px;padding:0 12px;font-size:14px;color:var(--muted);min-width:230px;justify-content:space-between}
.C .ttl{display:flex;align-items:baseline;gap:12px}
.C .ttl h1{font:800 34px/42px Manrope,sans-serif;letter-spacing:-.03em;margin:0}
.C .ttl span{color:var(--muted);font-size:15px}
.C .fbar{display:flex;align-items:center;gap:8px;margin:18px 0 20px;flex-wrap:wrap}
.C .fbar .sw{margin-left:auto;display:flex;align-items:center;gap:10px;font-size:14px;color:var(--ink2)}
.C .tog{width:40px;height:24px;border-radius:12px;background:var(--ink);position:relative}.C .tog:after{content:"";position:absolute;right:3px;top:3px;width:18px;height:18px;border-radius:9px;background:#fff}
.C .applied{display:flex;gap:8px;align-items:center;margin:-6px 0 18px;font-size:13.5px;color:var(--muted)}
.C .applied .chip{height:30px;background:var(--surf);border-color:var(--surf)}
.C .pager{display:flex;justify-content:center;align-items:center;gap:16px;margin-top:28px}
/* товар */
.C .pg2{display:grid;grid-template-columns:640px 1fr;gap:48px;align-items:start}
.C .gal .m{height:480px;border:1px solid var(--line);border-radius:20px;display:flex;align-items:center;justify-content:center;position:relative}
.C .gal .m img{max-height:330px;max-width:540px;object-fit:contain}
.C .gal .m .tags{position:absolute;left:16px;top:16px;display:flex;gap:6px}
.C .gal .m .src{position:absolute;left:18px;bottom:14px;font-size:12px;color:var(--muted)}
.C .gal .th{display:flex;gap:10px;margin-top:10px}
.C .gal .th span{width:96px;height:76px;border:1px solid var(--line);border-radius:12px;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:6px}
.C .gal .th span.on{border:2px solid var(--ink)}.C .gal .th img{max-height:60px;object-fit:contain}
.C .fitok{display:flex;gap:12px;align-items:center;border-radius:14px;background:var(--okbg);padding:12px 14px;color:var(--ok);font-size:14px;line-height:20px}
.C .fitok b{color:var(--ink);font-weight:800}
.C .fitok .lnk{margin-left:auto;font-size:13.5px;color:var(--ok)}
.C .fitok .c{width:30px;height:30px;border-radius:15px;background:var(--ok);color:#fff;display:flex;align-items:center;justify-content:center;flex:none}
.C .pi h1{font:800 30px/38px Manrope,sans-serif;letter-spacing:-.025em;margin:18px 0 10px}
.C .codes{display:flex;gap:8px;flex-wrap:wrap}
.C .codes span{height:30px;padding:0 12px;border-radius:15px;background:var(--surf);font-size:12.5px;display:flex;align-items:center;gap:6px;color:var(--ink2)}
.C .codes span em{font-style:normal;color:var(--muted)}
.C .specp{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:18px 0}
.C .specp div{border:1px solid var(--line);border-radius:14px;padding:12px 14px;font-size:15px;font-weight:700;line-height:20px}
.C .specp small{display:block;font-size:12px;font-weight:600;color:var(--muted);line-height:16px;margin-bottom:4px}
.C .pbx{border:1px solid var(--line);border-radius:20px;padding:20px 22px}
.C .pbx .p{display:flex;align-items:center;justify-content:space-between}
.C .pbx .p b{font-size:36px;line-height:42px;font-weight:800;letter-spacing:-.03em}
.C .pbx .p small{display:block;font-size:13px;color:var(--muted);font-weight:500}
.C .whs{display:flex;gap:8px;margin:14px 0 16px}
.C .whs div{flex:1;border-radius:12px;background:var(--surf);padding:10px 12px;font-size:12.5px;line-height:17px;color:var(--muted)}
.C .whs b{display:block;font-size:13.5px;color:var(--ink);font-weight:700}
.C .whs .ok{color:var(--ok)}.C .whs .ord{color:var(--warn)}
.C .qline{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.C .qline .qt{height:50px}.C .qline .qt span{width:44px;height:50px}.C .qline .qt i{width:30px;font-size:16px}
.C .qline .tot{margin-left:auto;text-align:right;font-size:12.5px;color:var(--muted);line-height:16px}.C .qline .tot b{display:block;font-size:20px;line-height:26px;color:var(--ink);font-weight:800}
.C .bb{display:grid;grid-template-columns:1.3fr 1fr;gap:10px}.C .bb .btn{height:52px}
.C .dl{display:grid;gap:8px;margin-top:16px;font-size:13.5px;line-height:20px;color:var(--ink2)}
.C .dl div{display:flex;gap:10px}.C .dl .ico{color:var(--muted);flex:none}
.C .segs{display:inline-flex;background:var(--surf);border-radius:14px;padding:4px;gap:2px;margin:48px 0 24px}
.C .segs span{height:42px;padding:0 18px;border-radius:11px;display:flex;align-items:center;gap:6px;font-size:14.5px;font-weight:700;color:var(--muted)}
.C .segs span.on{background:#fff;color:var(--ink);box-shadow:0 1px 3px rgba(0,0,0,.08)}
.C .segs em{font-style:normal;font-size:12px;color:var(--muted)}
.C .cmp{display:grid;grid-template-columns:1fr 420px;gap:40px}
.C .cmpbox{border:1px solid var(--line);border-radius:20px;padding:22px 24px}
.C .cmpbox .top{display:flex;gap:8px;align-items:center;margin-bottom:16px}
.C .cmpbox .inp{flex:1}
.C .mgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:0 24px}
.C .mgrid div{height:40px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line);font-size:14px}
.C .mgrid div .ico{color:var(--ok)}
.C .mgrid div.me{font-weight:800}
.C .mgrid div.me em{font-style:normal;margin-left:auto;font-size:11.5px;color:var(--red);font-weight:700}
.C .grp{font-size:12.5px;font-weight:800;color:var(--muted);letter-spacing:.04em;text-transform:uppercase;margin:18px 0 4px}
.C .grp:first-of-type{margin-top:0}
.C .spec2 div{display:flex;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid var(--line);font-size:14px}
.C .spec2 span{color:var(--muted)}.C .spec2 b{font-weight:700;text-align:right}
.C .ic3{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:40px}
.C .ic3 div{border-radius:16px;background:var(--surf);padding:20px 22px;font-size:14px;line-height:21px;color:var(--ink2)}
.C .ic3 b{display:flex;align-items:center;gap:10px;font-size:16px;color:var(--ink);font-weight:800;margin-bottom:8px}
.C .ic3 b .ico{color:var(--red)}
.C .note{font-size:12.5px;color:var(--muted);margin-top:12px}
/* мобильная */
.C.m{font-size:14.5px;line-height:22px}
.C .mh{height:56px;display:flex;align-items:center;justify-content:space-between;padding:0 4px 0 16px}
.C .mh .logo b{font-size:17px}.C .mh .mk{width:32px;height:32px;border-radius:9px}.C .mh .mk:before{left:7px;right:7px;top:10px}.C .mh .mk:after{left:10px;right:10px;top:17px;height:8px}
.C .mh .r{display:flex}
.C .i44{width:44px;height:44px;display:flex;align-items:center;justify-content:center;position:relative;color:var(--ink)}
.C .i44 .n{position:absolute;top:4px;right:2px;min-width:18px;height:18px;border-radius:9px;background:var(--red);color:#fff;font-size:11px;font-weight:700;line-height:18px;text-align:center;padding:0 5px}
.C .mmine{margin:0 16px;height:52px;border-radius:14px;background:var(--reds);border:1px solid var(--redl);display:flex;align-items:center;gap:12px;padding:0 12px 0 14px}
.C .mmine .ico{color:var(--red)}.C .mmine .t{flex:1}.C .mmine small{display:block;font-size:11.5px;line-height:14px;color:var(--muted);font-weight:600}.C .mmine b{display:block;font-size:14.5px;line-height:19px;font-weight:800}
.C .msearch{margin:10px 16px 0;height:48px;border-radius:12px;background:var(--surf);display:flex;align-items:center;gap:10px;padding:0 14px;color:var(--muted)}
.C .mhero{margin:16px 16px 0;border-radius:20px;background:var(--reds);padding:20px 16px 16px}
.C .mhero h1{font:800 26px/32px Manrope,sans-serif;letter-spacing:-.03em;margin:10px 0 14px}
.C .mhero .stp{display:grid;gap:8px}
.C .mhero .step{background:#fff;height:58px}
.C .mhero .btn{height:52px;width:100%}
.C .mhero .pop{margin-top:12px}
.C .msh{display:flex;justify-content:space-between;align-items:baseline;margin:30px 16px 12px}
.C .msh h2{font-size:21px;line-height:28px}
.C .msh a{font-size:14px;color:var(--red);font-weight:700}
.C .mbr{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:0 16px}
.C .mbr span{height:56px;border:1px solid var(--line);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:12.5px;font-weight:700;color:var(--ink2)}
.C .mcat{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:0 16px}
.C .mcat a{min-width:0;height:72px;border:1px solid var(--line);border-radius:14px;display:grid;grid-template-columns:56px 1fr;gap:10px;align-items:center;padding:7px 10px 7px 7px}
.C .mcat .im{width:56px;height:56px;border-radius:10px;background:var(--tile);overflow:hidden}.C .mcat img{width:100%;height:100%;object-fit:cover}
.C .mcat b{font-size:13px;line-height:17px;font-weight:700;overflow-wrap:anywhere;min-width:0}
.C .mg2{display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:0 16px}
.C .mg2 .pc{padding:8px;border-radius:14px}
.C .mg2 .pc .im{height:112px}
.C .mg2 .pc .im img{max-height:72px;max-width:140px}
.C .mg2 .pc .bd{padding:10px 4px 2px}
.C .mg2 .pc h3{font-size:13.5px;line-height:18px;height:54px}
.C .mg2 .pc .pr{margin:8px 0}
.C .mg2 .pc .pr b{font-size:17px}
.C .mg2 .pc .btn{height:44px;width:100%;font-size:14px}
.C .mfoot{margin-top:28px;border-top:1px solid var(--line);padding:18px 16px 96px;font-size:13px;line-height:20px;color:var(--muted)}
.C .mfoot b{display:block;font-size:17px;color:var(--ink);font-weight:800}
.C .tabs{position:absolute;left:0;right:0;bottom:0;height:66px;background:#fff;border-top:1px solid var(--line);display:grid;grid-template-columns:repeat(5,1fr)}
.C .tabs span{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;font-size:11px;font-weight:700;color:var(--muted);position:relative}
.C .tabs span.on{color:var(--ink)}
.C .tabs span.mid .c{width:44px;height:44px;border-radius:14px;background:var(--red);color:#fff;display:flex;align-items:center;justify-content:center;margin-top:-14px}
.C .tabs .n{position:absolute;top:6px;left:calc(50% + 4px);min-width:18px;height:18px;border-radius:9px;background:var(--red);color:#fff;font-size:11px;line-height:18px;text-align:center;padding:0 5px}
.C .mtop{height:52px;display:flex;align-items:center;justify-content:space-between;padding:0 4px}
.C .mfit{margin:0 16px;display:flex;gap:10px;align-items:center;border-radius:14px;background:var(--okbg);padding:10px 12px;font-size:13px;line-height:18px;color:var(--ok)}
.C .mfit b{color:var(--ink);font-weight:800}
.C .mfit .c{width:26px;height:26px;border-radius:13px;background:var(--ok);color:#fff;display:flex;align-items:center;justify-content:center;flex:none}
.C .mgal{margin:10px 16px 0;height:206px;border:1px solid var(--line);border-radius:18px;display:flex;align-items:center;justify-content:center;position:relative}
.C .mgal img{max-height:150px;max-width:300px;object-fit:contain}
.C .mgal .dots{position:absolute;bottom:12px;display:flex;gap:6px}.C .mgal .dots i{width:6px;height:6px;border-radius:3px;background:#D9D5D1}.C .mgal .dots i.on{width:18px;background:var(--ink)}
.C .mpi{padding:0 16px}
.C .mpi h1{font:800 21px/27px Manrope,sans-serif;letter-spacing:-.02em;margin:14px 0 8px}
.C .mpi .codes span{height:28px;font-size:12px}
.C .msp{display:flex;gap:6px;margin:10px 16px 0}
.C .msp span{flex:1;border:1px solid var(--line);border-radius:12px;padding:7px 10px;font-size:13.5px;font-weight:700;line-height:18px}
.C .msp small{display:block;font-size:11px;color:var(--muted);font-weight:600;line-height:14px}
.C .mpb{margin:10px 16px 0;border:1px solid var(--line);border-radius:18px;padding:14px}
.C .mpb .p{display:flex;justify-content:space-between;align-items:center}.C .mpb .p b{font-size:28px;line-height:34px;font-weight:800;letter-spacing:-.02em}
.C .mpb .qline{margin:10px 0}.C .mpb .qline .qt{height:48px}.C .mpb .qline .qt span{width:44px;height:48px}
.C .mpb .bb{grid-template-columns:1fr;gap:8px}.C .mpb .bb .btn{height:50px}
.C .msegs{overflow:hidden;display:flex;gap:6px;padding:8px 16px;border-bottom:1px solid var(--line);background:#fff;white-space:nowrap}
.C .msegs span{height:36px;padding:0 14px;border-radius:18px;display:flex;align-items:center;font-size:13.5px;font-weight:700;color:var(--muted);background:var(--surf)}
.C .msegs span.on{background:var(--ink);color:#fff}
.C .mcmp{padding:14px 16px}
.C .mcmp .inp{height:46px;margin-bottom:12px;background:var(--surf);border-color:var(--surf)}
.C .mcmp .row{height:44px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--line);font-size:14px}
.C .mcmp .row .ico{color:var(--ok)}.C .mcmp .row.me{font-weight:800}.C .mcmp .row.me em{font-style:normal;margin-left:auto;font-size:11.5px;color:var(--red)}
.C .sbar{position:absolute;left:0;right:0;bottom:0;background:#fff;border-top:1px solid var(--line);box-shadow:0 -10px 28px rgba(0,0,0,.06);padding:10px 16px 14px;display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center}
.C .sbar .p{font-size:12px;line-height:15px;color:var(--muted);font-weight:600}.C .sbar .p b{display:block;font-size:18px;line-height:23px;color:var(--ink);font-weight:800}
.C .sbar .qt{height:46px}.C .sbar .qt span{width:34px;height:46px}
.C .sbar .btn{height:48px;padding:0 16px}
`;

const logo = (href = 'C-home.html') => `<a class="logo" href="${href}"><span class="mk"></span><span><b>Sakura Russia</b><small>Расходные материалы для печати</small></span></a>`;
function header(mine = true) {
  return `<header class="hdr"><div class="wrap">${logo()}<div class="search">${icon('search', 19)}Поиск по модели принтера, номеру или названию</div>
<a class="mine">${icon('printer', 22)}<span>${mine ? '<small>Мой принтер</small><b>HP LaserJet P1102</b>' : '<small>Мой принтер</small><b>Не выбран</b>'}</span>${icon('down', 18)}</a>
<div class="icons"><span class="i48">${icon('heart', 22)}</span><span class="i48">${icon('compare', 22)}</span><span class="i48">${icon('cart', 22)}<b class="n">2</b></span><span class="i48">${icon('user', 22)}</span></div></div></header>
<nav class="nav"><div class="wrap"><a class="all">${icon('menu', 18)}Все категории</a>${CATEGORIES.map(c => `<a ${c.id === 'laser' ? 'href="C-catalog.html"' : ''}>${c.name}</a>`).join('')}<span class="sp">${STORE.phone}</span></div></nav>`;
}
function footer() {
  return `<footer class="ftr"><div class="wrap"><div class="fg"><div>${logo()}<p>Картриджи, фотобумага, широкоформатная бумага, проекционные экраны и крепления. Розница по России.</p></div>
<div><h4>Каталог</h4>${CATEGORIES.slice(0, 5).map(c => `<a>${c.name}</a>`).join('')}</div>
<div><h4>Покупателям</h4><a>Подбор картриджа</a><a>Доставка и оплата</a><a>Гарантия и возврат</a><a>Помощь и FAQ</a><a>Личный кабинет</a></div>
<div><h4>Компания</h4><a>О компании</a><a>Контакты</a><a>Реквизиты</a><a>Политика конфиденциальности</a></div>
<div><h4>Связаться</h4><a><b>${STORE.phone}</b></a><a>${STORE.email}</a><p>${STORE.address}<br>${STORE.hours}</p></div></div>
<div class="fb"><span>© 2026 Sakura Russia · Реквизиты продавца указываются после согласования</span><span>Демонстрационный макет: цены, остатки и сроки условные</span></div></div></footer>`;
}
const qt = (n = 1) => `<span class="qt"><span>${icon('minus', 16)}</span><i>${n}</i><span>${icon('plus', 16)}</span></span>`;
function pcard(p) {
  const st = p.stock;
  const btn = st === 'in' ? `${qt()}<a class="btn s">${icon('cart', 17)}В корзину</a>` : st === 'order' ? `${qt()}<a class="btn s soft">Заказать</a>` : `<a class="btn s out">Сообщить о поступлении</a>`;
  return `<article class="pc"><a class="im" href="C-product.html"><img src="${asset(`products/${p.img}.jpg`)}" alt=""><span class="tg"><span class="tag ${st === 'in' ? 'ok' : st}">${stockLabel[st]}</span></span><span class="fv">${icon('heart', 18)}</span></a>
<div class="bd"><h3>${esc(p.name)}</h3><div class="fit">${icon('printer', 15)}<span>${esc(p.fits)}</span></div><div class="sp"><span>${num(p.yield)} стр.</span><span><i class="dot" style="background:${colorDot[p.color]}"></i>${p.color}</span></div>
<div class="pr"><b>${rub(p.price)}</b></div><div class="buy">${btn}</div></div></article>`;
}

export function home() {
  const body = `<div class="screen C" data-name="C / 01 Главная / Desktop 1440" style="width:1440px">${header(false)}
<section class="heroC"><div class="wrap"><div><span class="eb">${icon('printer', 16)}Подбор по принтеру</span><h1>Картриджи, которые подходят вашему принтеру</h1>
<div class="fcard"><div class="step"><span class="num d">${icon('check', 15, 2.4)}</span><div class="v"><label>Производитель</label><b>HP</b></div>${icon('down', 18)}</div><div class="step"><span class="num">2</span><div class="v"><label>Модель принтера</label><b style="color:var(--muted);font-weight:600">Например, LaserJet P1102</b></div>${icon('search', 18)}</div><a class="btn">Показать ${icon('arrow', 18)}</a></div>
<div class="pop">Часто выбирают: <span class="chip">HP LaserJet P1102</span><span class="chip">Canon i-SENSYS MF3010</span><span class="chip">Kyocera ECOSYS M2040dn</span><span class="chip">HP Laser 107a</span></div>
<div class="how">${icon('info', 17)}Модель указана на передней панели или на наклейке снизу корпуса. <a class="lnk">Как найти модель</a></div></div>
<aside class="dev"><div class="top"><span>ВАШ ПРИНТЕР</span><span class="tag out">Не выбран</span></div><img src="${asset('concept05/printer-line.jpg')}" alt=""><b>Сохраните принтер</b><span style="font-size:13.5px;color:var(--muted)">Каталог и поиск будут показывать только подходящие товары</span>
<div class="r"><span><b>Каталог</b>только совместимые товары</span><span><b>Поиск</b>с учётом модели</span></div></aside></div></section>
<main class="wrap"><section class="brandsC">${BRANDS.map((b, i) => { const s = logoSize(b, 1250, 26); return `<a class="${i === 0 ? 'on' : ''}"><img src="${asset(b.file)}" alt="${b.name}" style="height:${s.h}px;width:${s.w}px"></a>`; }).join('')}<a class="more">Ещё 6 ${icon('right', 16)}</a></section>
<div class="sh"><div class="l"><h2>Для HP LaserJet P1102</h2><small>Пример выдачи после выбора принтера</small></div><a class="lnk">Все товары для этого принтера ${icon('arrow', 17)}</a></div>
<section class="fitrow"><div class="fitnote"><b>2 подходящих картриджа</b><p>Совместимость указана по данным каталога. Если модели нет в списке, менеджер проверит её по запросу.</p><a class="lnk">Проверить другую модель ${icon('arrow', 16)}</a></div>
<article class="hcard"><div class="im"><img src="${asset('products/ce285a.jpg')}" alt=""></div><div><span class="tag ok">${icon('check', 13, 2.4)}Подходит</span><h3>Картридж SAKURA CE285A / CB435A / 436A / 725</h3><div class="m">2 000 стр. · Чёрный · аналог HP 85A</div><div class="b"><b>425 ₽</b><a class="btn s">${icon('cart', 17)}В корзину</a></div></div></article>
<article class="hcard"><div class="im noimg">${icon('box', 30, 1.3)}<span>Фото появится после загрузки</span></div><div><span class="tag ok">${icon('check', 13, 2.4)}Подходит</span><h3>Картридж SAKURA CE285A для HP P1100, P1102</h3><div class="m">2 000 стр. · Чёрный · аналог HP 85A</div><div class="b"><b>425 ₽</b><a class="btn s">${icon('cart', 17)}В корзину</a></div></div></article></section>
<div class="sh"><div class="l"><h2>Категории</h2></div><a class="lnk">Весь каталог ${icon('arrow', 17)}</a></div>
<section class="catsC">${CATEGORIES.map(c => `<a ${c.id === 'laser' ? 'href="C-catalog.html"' : ''}><span class="im"><img src="${asset(c.img)}" alt=""></span><span><b>${c.name}</b><small>${c.note}</small></span>${icon('right', 18)}</a>`).join('')}</section>
<div class="sh"><div class="l"><h2>Популярные картриджи</h2></div><div style="display:flex;gap:6px"><span class="chip on">Все</span><span class="chip">HP</span><span class="chip">Canon</span><span class="chip">Kyocera</span></div></div>
<section class="g4">${['ce285a', 'tk1170', 'w1106a', 'cb540a'].map(id => pcard(P[id])).join('')}</section>
<section class="ask"><div><b>Не нашли модель принтера?</b><p>Оставьте модель и телефон. Менеджер проверит совместимость и перезвонит в рабочее время.</p></div><div class="f"><span class="inp">Модель принтера</span><span class="inp">Телефон</span><a class="btn">Отправить</a></div></section>
</main>${footer()}</div>`;
  return page({ title: 'C / Главная', families: FAM, css: CSS, body, width: 1440 });
}

export function catalog() {
  const body = `<div class="screen C" data-name="C / 02 Каталог / Desktop 1440" style="width:1440px">${header(false)}<main class="wrap">
<div class="crumbs">Главная ${icon('right', 14)} Каталог ${icon('right', 14)} <span style="color:var(--ink)">Лазерные картриджи</span></div>
<div class="ctx">${icon('printer', 24)}<span class="t"><b>Выберите принтер</b>, чтобы в каталоге остались только совместимые картриджи</span><span class="sel">HP ${icon('down', 16)}</span><span class="sel">Модель принтера ${icon('search', 16)}</span><a class="btn s">Применить</a></div>
<div class="ttl"><h1>Лазерные картриджи</h1><span>12 товаров в демокаталоге</span></div>
<div class="fbar"><span class="chip on dd">Производитель: HP ${icon('down', 16)}</span><span class="chip dd">Серия принтера ${icon('down', 16)}</span><span class="chip dd">Цвет ${icon('down', 16)}</span><span class="chip dd">Ресурс ${icon('down', 16)}</span><span class="chip dd">Цена ${icon('down', 16)}</span><span class="chip dd">Ещё фильтры ${icon('sliders', 16)}</span>
<div class="sw"><span class="tog"></span>Только в наличии<span class="chip dd" style="margin-left:8px">По популярности ${icon('down', 16)}</span></div></div>
<div class="applied">Выбрано: <span class="chip">HP ${icon('close', 14)}</span><span class="chip">В наличии ${icon('close', 14)}</span><a class="lnk" style="font-size:13.5px">Сбросить всё</a></div>
<section class="g4">${['ce285a', 'cb435a', 'cb436a', 'w1106a', 'cb540a', 'cb541a', 'cb542a', 'cb543a'].map(id => pcard(P[id])).join('')}</section>
<div class="pager"><a class="btn out">Показать ещё 4</a><span style="color:var(--muted);font-size:14px">Показано 8 из 12</span></div>
</main>${footer()}</div>`;
  return page({ title: 'C / Каталог', families: FAM, css: CSS, body, width: 1440 });
}

export function product() {
  const p = P.ce285a;
  const mine = 'P1102';
  const body = `<div class="screen C" data-name="C / 03 Товар CE285A / Desktop 1440" style="width:1440px">${header(true)}<main class="wrap">
<div class="crumbs">Главная ${icon('right', 14)} Лазерные картриджи ${icon('right', 14)} HP ${icon('right', 14)} <span style="color:var(--ink)">CE285A</span></div>
<section class="pg2"><div class="gal"><div class="m"><img src="${asset('products/ce285a.jpg')}" alt=""><div class="tags"><span class="tag ok">В наличии</span></div><span class="src">Фото упаковки · sakurarussia.ru</span></div><div class="th"><span class="on"><img src="${asset('products/ce285a.jpg')}" alt=""></span><span><img src="${asset('products/ce285a-label.jpg')}" alt=""></span><span><img src="${asset('products/ce285a-end.jpg')}" alt=""></span></div></div>
<div class="pi"><div class="fitok"><span class="c">${icon('check', 17, 2.4)}</span><span>Подходит к вашему принтеру <b>HP LaserJet P1102</b></span><a class="lnk">Изменить</a></div>
<h1>${esc(p.name)}</h1><div class="codes"><span><em>Артикул</em>${p.sku}${icon('copy', 14)}</span><span><em>Аналог</em>HP 85A, 35A, 36A, Canon 725</span></div>
<div class="specp"><div><small>Ресурс</small>2 000 стр.</div><div><small>Цвет</small><i class="dot" style="background:#1d1d1f"></i> Чёрный</div><div><small>Печать</small>Лазерная</div><div><small>Подходит</small>17 моделей</div></div>
<div class="pbx"><div class="p"><div><b>${rub(p.price)}</b><small>за 1 шт.</small></div><div style="display:flex;gap:6px"><span class="i48" style="background:var(--surf)">${icon('heart', 20)}</span><span class="i48" style="background:var(--surf)">${icon('compare', 20)}</span></div></div>
<div class="whs"><div><b>Москва, ${STORE.addressShort}</b><span class="ok">В наличии · самовывоз</span></div><div><b>Склад поставщика</b><span class="ord">Под заказ · срок уточнит менеджер</span></div></div>
<div class="qline">${qt(2)}<div class="tot">Итого за 2 шт.<b>850 ₽</b></div></div>
<div class="bb"><a class="btn">${icon('cart', 19)}В корзину</a><a class="btn soft">Купить в 1 клик</a></div>
<div class="dl"><div>${icon('truck', 18)}Доставка СДЭК до двери или пункта выдачи: расчёт при оформлении</div><div>${icon('card', 18)}Оплата картой или через СБП</div></div></div></div></section>
<div class="segs"><span class="on">Совместимость <em>17</em></span><span>Характеристики</span><span>Описание</span><span>Доставка и оплата</span><span>Гарантия и возврат</span><span>Документы</span></div>
<section class="cmp"><div class="cmpbox"><div class="top"><span class="inp">${icon('search', 18)}Найти модель принтера</span><span class="chip on">Все 17</span><span class="chip">HP 12</span><span class="chip">Canon 5</span></div>
${Object.entries(CE285A_COMPAT).map(([s, ms]) => `<div class="grp">${s}</div><div class="mgrid">${ms.map(m => `<div class="${m === mine ? 'me' : ''}">${icon('check', 16, 2.2)}${s.split(' ')[0]} ${m}${m === mine ? '<em>Ваш принтер</em>' : ''}</div>`).join('')}</div>`).join('')}
<div class="note">Список приведён по названию товара в текущем магазине и требует проверки перед публикацией.</div></div>
<div><h2 style="font-size:20px;line-height:28px;margin-bottom:8px">Характеристики</h2><div class="spec2">${CE285A_SPECS.map(([k, v]) => `<div><span>${k}</span><b>${v}</b></div>`).join('')}</div></div></section>
<section class="ic3"><div><b>${icon('truck', 20)}Доставка и оплата</b>СДЭК до двери или пункта выдачи, самовывоз в Москве. Оплата картой или через СБП.</div><div><b>${icon('shield', 20)}Гарантия и возврат</b>Порядок описан на отдельной странице. Срок гарантии подтверждает продавец.</div><div><b>${icon('file', 20)}Документы</b>Инструкция по установке появится после загрузки в каталог.</div></section>
<div class="sh"><div class="l"><h2>Ещё для HP LaserJet P1102</h2><small>Совместимые товары из других категорий появятся после наполнения каталога</small></div></div>
<section class="g4">${['cb435a', 'cb436a', 'w1106a', 'c7115a'].map(id => pcard(P[id])).join('')}</section>
</main>${footer()}</div>`;
  return page({ title: 'C / Товар', families: FAM, css: CSS, body, width: 1440 });
}

export function mHome() {
  const body = `<div class="screen C m" data-name="C / 04 Главная / Mobile 390" style="width:390px">
<div class="mh">${logo().replace('<small>Расходные материалы для печати</small>', '')}<div class="r"><span class="i44">${icon('search', 22)}</span><span class="i44">${icon('cart', 22)}<b class="n">2</b></span></div></div>
<a class="mmine">${icon('printer', 22)}<span class="t"><small>Мой принтер</small><b>Не выбран</b></span><span class="lnk" style="font-size:13.5px">Выбрать</span></a>
<section class="mhero"><span class="eb">${icon('printer', 15)}Подбор по принтеру</span><h1>Картриджи, которые подходят вашему принтеру</h1>
<div class="stp"><div class="step"><span class="num d">${icon('check', 14, 2.4)}</span><div class="v"><label>Производитель</label><b>HP</b></div>${icon('down', 18)}</div><div class="step"><span class="num">2</span><div class="v"><label>Модель принтера</label><b style="color:var(--muted);font-weight:600">Например, P1102</b></div>${icon('search', 18)}</div><a class="btn">Показать картриджи</a></div>
<div class="pop"><span class="chip">LaserJet P1102</span><span class="chip">i-SENSYS MF3010</span><span class="chip">ECOSYS M2040dn</span></div></section>
<div class="msh"><h2>Производитель</h2><a>Все</a></div>
<section class="mbr">${BRANDS.map(b => { const s = logoSize(b, 700, 22); return `<span><img src="${asset(b.file)}" alt="${b.name}" style="height:${s.h}px;width:${s.w}px"></span>`; }).join('')}<span>Ещё 6</span></section>
<div class="msh"><h2>Категории</h2><a>Все</a></div>
<section class="mcat">${CATEGORIES.map(c => `<a><span class="im"><img src="${asset(c.img)}" alt=""></span><b>${c.name}</b></a>`).join('')}</section>
<div class="msh"><h2>Популярные</h2><a>Все</a></div>
<section class="mg2">${['ce285a', 'tk1170'].map(id => { const p = P[id]; return `<article class="pc"><a class="im" href="C-m-product.html"><img src="${asset(`products/${p.img}.jpg`)}" alt=""><span class="fv">${icon('heart', 17)}</span></a><div class="bd"><h3>${esc(p.name)}</h3><div class="sp"><span>${num(p.yield)} стр.</span></div><div class="pr"><b>${rub(p.price)}</b><span class="tag ok">В наличии</span></div><a class="btn">${icon('cart', 17)}В корзину</a></div></article>`; }).join('')}</section>
<div class="mfoot"><b>${STORE.phone}</b>${STORE.hours}<br>Доставка и оплата · Гарантия и возврат · О компании · Контакты · Помощь<br><br>Демонстрационный макет: цены и остатки условные</div>
<nav class="tabs"><span class="on">${icon('home', 22)}Главная</span><span>${icon('menu', 22)}Каталог</span><span class="mid"><span class="c">${icon('printer', 22)}</span>Подбор</span><span>${icon('cart', 22)}<b class="n">2</b>Корзина</span><span>${icon('user', 22)}Профиль</span></nav></div>`;
  return page({ title: 'C / Mobile главная', families: FAM, css: CSS, body, width: 390 });
}

export function mProduct() {
  const p = P.ce285a;
  const body = `<div class="screen C m" data-name="C / 05 Товар: первый экран / Mobile 390" style="width:390px;height:844px">
<div class="mtop"><span class="i44">${icon('left', 22)}</span><div style="display:flex"><span class="i44">${icon('heart', 21)}</span><span class="i44">${icon('share', 21)}</span><span class="i44">${icon('cart', 21)}<b class="n">2</b></span></div></div>
<div class="mfit"><span class="c">${icon('check', 15, 2.4)}</span><span>Подходит к <b>HP LaserJet P1102</b></span></div>
<div class="mgal"><img src="${asset('products/ce285a.jpg')}" alt=""><div class="dots"><i class="on"></i><i></i><i></i></div></div>
<div class="mpi"><h1>${esc(p.name)}</h1><div class="codes"><span><em>Артикул</em>${p.sku}</span></div></div>
<div class="msp"><span><small>Ресурс</small>2 000 стр.</span><span><small>Цвет</small>Чёрный</span><span><small>Подходит</small>17 моделей</span></div>
<section class="mpb"><div class="p"><b>${rub(p.price)}</b><span class="tag ok">В наличии</span></div><div class="qline">${qt(2)}<div class="tot">Итого<b>850 ₽</b></div></div><div class="bb"><a class="btn">${icon('cart', 19)}В корзину</a><a class="btn soft">Купить в 1 клик</a></div></section></div>`;
  return page({ title: 'C / Mobile товар', families: FAM, css: CSS, body, width: 390 });
}

export function mProductScroll() {
  const all = Object.entries(CE285A_COMPAT).flatMap(([s, ms]) => ms.map(m => [s.split(' ')[0], m]));
  const body = `<div class="screen C m" data-name="C / 06 Товар: совместимость и панель покупки / Mobile 390" style="width:390px;height:844px">
<div class="mtop"><span class="i44">${icon('left', 22)}</span><b style="font-size:14px">CE285A / CB435A / 436A / 725</b><span class="i44">${icon('share', 21)}</span></div>
<div class="msegs"><span class="on">Совместимость 17</span><span>Характеристики</span><span>Описание</span></div>
<div class="mcmp"><span class="inp">${icon('search', 18)}Найти модель принтера</span>${all.slice(0, 11).map(([b, m]) => `<div class="row ${m === 'P1102' ? 'me' : ''}">${icon('check', 16, 2.2)}${b} ${m}${m === 'P1102' ? '<em>Ваш принтер</em>' : ''}</div>`).join('')}<div class="row" style="color:var(--red);font-weight:700;border:0">Показать ещё 6</div></div>
<div class="sbar"><div class="p">2 шт. × 425 ₽<b>850 ₽</b></div>${qt(2)}<a class="btn">В корзину</a></div></div>`;
  return page({ title: 'C / Mobile товар, совместимость', families: FAM, css: CSS, body, width: 390 });
}
