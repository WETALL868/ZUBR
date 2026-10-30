// Направление B «Прайс»: строгая типографика IBM Plex, плотная коммерческая сетка, таблицы, постоянное дерево каталога.
import { asset, icon, rub, num, esc, STORE, P, PRODUCTS, CATEGORIES, PRINTER_BRANDS, CE285A_COMPAT, CE285A_SPECS, stockLabel, colorDot, page } from './common.mjs';

const FAM = ['IBM Plex Sans', 'IBM Plex Mono'];
const CSS = `
.B{--bg:#F4F5F6;--ink:#121417;--ink2:#3D434B;--muted:#6A727C;--line:#DCE0E4;--line2:#EBEEF0;--red:#C5302D;--redd:#A82623;--reds:#FCEEED;--ok:#1F7A45;--warn:#8F5F00;
  background:#fff;color:var(--ink);font:400 14px/20px 'IBM Plex Sans',sans-serif}
.B .mono{font-family:'IBM Plex Mono',monospace}
.B .wrap{width:1376px;margin:0 auto}
.B .rl{height:3px;background:var(--red)}
.B .hdr .wrap{display:flex;align-items:center;gap:28px;height:68px}
.B .logo{display:flex;flex-direction:column;flex:none;gap:2px}
.B .logo b{font:500 17px/20px 'IBM Plex Mono',monospace;letter-spacing:.06em;display:flex;align-items:center;gap:8px}
.B .logo b i{width:10px;height:10px;background:var(--red);display:inline-block}
.B .logo small{font-size:11.5px;line-height:14px;color:var(--muted);letter-spacing:.01em}
.B .search{flex:1;height:42px;border:1px solid #C9CED4;border-radius:4px;display:flex;align-items:stretch;overflow:hidden}
.B .search .scope{display:flex;align-items:center;gap:6px;padding:0 12px;background:var(--bg);border-right:1px solid var(--line);font-size:13px;color:var(--ink2)}
.B .search .q{flex:1;display:flex;align-items:center;gap:8px;padding:0 12px;color:var(--muted);white-space:nowrap;overflow:hidden}
.B .search .go{display:flex;align-items:center;padding:0 18px;background:var(--ink);color:#fff;font-weight:500;font-size:13.5px}
.B .ph{font-size:13px;line-height:17px;text-align:right}.B .ph b{display:block;font-weight:600;font-size:14.5px}.B .ph small{color:var(--muted)}
.B .acts{display:flex;gap:2px}
.B .act{display:flex;align-items:center;gap:7px;height:40px;padding:0 10px;border-radius:4px;font-size:13px;color:var(--ink2)}
.B .act .n{font-family:'IBM Plex Mono',monospace;font-size:12px;background:var(--bg);border-radius:3px;padding:1px 5px;color:var(--ink)}
.B .act .n.r{background:var(--red);color:#fff}
.B .bar2{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:#fff}
.B .bar2 .wrap{display:flex;align-items:center;height:42px;gap:0}
.B .bar2 a{height:42px;display:flex;align-items:center;padding:0 14px;font-size:13.5px;color:var(--ink2);border-right:1px solid var(--line2)}
.B .bar2 a:first-child{padding-left:0}
.B .bar2 a em{font-style:normal;font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--muted);margin-left:6px}
.B .bar2 .r{margin-left:auto;border-right:0;color:var(--red);font-weight:500;gap:6px}
.B .bar2 .r2{border-right:0;padding-right:0}
.B .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;height:40px;padding:0 16px;border-radius:4px;background:var(--red);color:#fff;font-weight:500;font-size:14px;border:0;white-space:nowrap}
.B .btn.s{height:34px;padding:0 12px;font-size:13px}
.B .btn.k{background:var(--ink)}
.B .btn.o{background:#fff;color:var(--ink);border:1px solid #C9CED4}
.B .lnk{color:var(--red);font-weight:500;display:inline-flex;align-items:center;gap:4px}
.B .lbl{font:500 11.5px/14px 'IBM Plex Mono',monospace;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
.B h1{font-weight:600;font-size:26px;line-height:32px;letter-spacing:-.01em;margin:0}
.B h2{font-weight:600;font-size:18px;line-height:24px;margin:0}
.B .top3{display:grid;grid-template-columns:288px 1fr 336px;gap:24px;margin-top:24px}
.B .tree{border:1px solid var(--line);border-radius:4px}
.B .tree .h{padding:12px 16px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center}
.B .tree a{display:flex;align-items:center;justify-content:space-between;height:40px;padding:0 16px;font-size:14px;border-top:1px solid var(--line2)}
.B .tree a:nth-child(2){border-top:0}
.B .tree a span{display:flex;align-items:center;gap:10px}
.B .tree a em{font-style:normal;font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--muted)}
.B .tree a.on{background:var(--bg);font-weight:500}
.B .tree a.sub{padding-left:42px;font-size:13.5px;color:var(--ink2);height:34px}
.B .ci{width:20px;height:20px;color:var(--ink2)}
.B .lead{border:1px solid var(--line);border-radius:4px;padding:24px 28px 26px;background:linear-gradient(180deg,#fff, #fff 60%, var(--bg))}
.B .lead h1{font-size:30px;line-height:36px;margin:10px 0 8px;max-width:640px}
.B .lead p{margin:0 0 20px;color:var(--ink2);font-size:15px;line-height:22px;max-width:620px}
.B .sel3{display:grid;grid-template-columns:1fr 1.3fr 1fr auto;gap:8px}
.B .f{height:44px;border:1px solid #C9CED4;border-radius:4px;display:flex;align-items:center;justify-content:space-between;padding:0 12px;background:#fff}
.B .f .v{display:flex;flex-direction:column}.B .f label{font-size:11px;line-height:13px;color:var(--muted)}.B .f .t{font-size:14px;line-height:18px}.B .f .t.p{color:var(--muted)}
.B .sel3 .btn{height:44px}
.B .hint{margin-top:12px;font-size:12.5px;color:var(--muted);display:flex;gap:16px;flex-wrap:wrap}
.B .hint b{font-family:'IBM Plex Mono',monospace;font-weight:500;color:var(--ink2);border-bottom:1px dotted #9AA2AB}
.B .kpi{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--line);margin-top:24px;padding-top:16px;gap:20px}
.B .kpi div{font-size:13px;line-height:18px;color:var(--muted)}.B .kpi b{display:block;color:var(--ink);font-size:14px;font-weight:600;margin-bottom:2px}
.B .info{border:1px solid var(--line);border-radius:4px}
.B .info .row{display:grid;grid-template-columns:22px 1fr;gap:12px;padding:14px 16px;border-top:1px solid var(--line2);font-size:13px;line-height:18px;color:var(--muted)}
.B .info .row:first-child{border-top:0}
.B .info .row b{display:block;color:var(--ink);font-weight:600;font-size:13.5px;margin-bottom:2px}
.B .info .ico{color:var(--red)}
.B .brandidx{margin-top:24px;border:1px solid var(--line);border-radius:4px;display:grid;grid-template-columns:200px 1fr;align-items:center}
.B .brandidx .h{padding:14px 16px;border-right:1px solid var(--line)}
.B .brandidx .l{display:flex;flex-wrap:wrap;gap:0;padding:6px 8px}
.B .brandidx .l a{height:34px;display:flex;align-items:center;padding:0 10px;font-size:14px;font-weight:500;color:var(--ink2);gap:6px}
.B .brandidx .l a em{font-style:normal;font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--muted);font-weight:400}
.B .shead{display:flex;align-items:center;justify-content:space-between;margin:32px 0 12px}
.B .shead .l{display:flex;align-items:baseline;gap:14px}
.B .seg{display:flex;border:1px solid #C9CED4;border-radius:4px;overflow:hidden}
.B .seg span{height:32px;padding:0 12px;display:flex;align-items:center;font-size:13px;color:var(--ink2);border-left:1px solid var(--line)}
.B .seg span:first-child{border-left:0}.B .seg span.on{background:var(--ink);color:#fff}
.B table{width:100%;border-collapse:collapse}
.B .tbl{border:1px solid var(--line);border-radius:4px;overflow:hidden}
.B th{height:36px;background:var(--bg);text-align:left;font:500 11.5px/14px 'IBM Plex Mono',monospace;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);padding:0 12px;border-bottom:1px solid var(--line)}
.B td{height:64px;padding:8px 12px;border-top:1px solid var(--line2);vertical-align:middle;font-size:13.5px}
.B tr:nth-child(2) td{border-top:0}
.B td.im{width:76px;padding:6px 6px 6px 12px}.B td.im img{width:60px;height:44px;object-fit:contain}
.B td .nm{font-weight:500;font-size:14px;line-height:19px;color:var(--ink)}
.B td .ft{font-size:12.5px;line-height:17px;color:var(--muted);margin-top:2px}
.B td.c{font-family:'IBM Plex Mono',monospace;font-size:12.5px;color:var(--ink2);white-space:nowrap}
.B td.n{font-family:'IBM Plex Mono',monospace;text-align:right;white-space:nowrap}
.B td.pr{font-weight:600;font-size:15px;text-align:right;white-space:nowrap}
.B .stk{display:inline-flex;align-items:center;gap:6px;font-size:12.5px;white-space:nowrap;color:var(--ok)}
.B .stk i{width:7px;height:7px;border-radius:1px;background:currentColor}
.B .stk.order{color:var(--warn)}.B .stk.out{color:var(--muted)}
.B .clr{display:inline-flex;align-items:center;gap:6px;white-space:nowrap}.B .clr i{width:10px;height:10px;border-radius:2px}
.B .q{display:inline-flex;align-items:center;height:34px;border:1px solid #C9CED4;border-radius:4px}
.B .q span{width:28px;height:32px;display:flex;align-items:center;justify-content:center;color:var(--ink2)}
.B .q i{font-style:normal;width:28px;text-align:center;font-family:'IBM Plex Mono',monospace;font-size:13px}
.B td.act{white-space:nowrap;width:200px}
.B td.act .w{display:flex;justify-content:flex-end;align-items:center;gap:6px}
.B .cats6{display:grid;grid-template-columns:repeat(6,1fr);border:1px solid var(--line);border-radius:4px;overflow:hidden}
.B .cats6 a{padding:16px;border-left:1px solid var(--line);display:flex;flex-direction:column;gap:10px;min-height:118px}
.B .cats6 a:first-child{border-left:0}
.B .cats6 b{font-weight:600;font-size:14px;line-height:19px}
.B .cats6 small{font-size:12.5px;line-height:17px;color:var(--muted)}
.B .cats6 em{font-style:normal;font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--muted);margin-top:auto}
.B .cats6 .ci{width:28px;height:28px;color:var(--red)}
.B .help{margin:32px 0 0;display:grid;grid-template-columns:1fr 1fr;gap:24px}
.B .help>div{border:1px solid var(--line);border-radius:4px;padding:20px 24px}
.B .help p{margin:6px 0 14px;color:var(--ink2)}
.B .help ol{margin:0;padding:0;list-style:none;display:grid;gap:8px;counter-reset:s}
.B .help li{display:grid;grid-template-columns:28px 1fr;font-size:13.5px;color:var(--ink2)}
.B .help li:before{counter-increment:s;content:counter(s,decimal-leading-zero);font-family:'IBM Plex Mono',monospace;color:var(--red);font-size:12.5px}
.B .ftr{margin-top:40px;border-top:1px solid var(--line);background:var(--bg)}
.B .fg{display:grid;grid-template-columns:300px repeat(4,1fr);gap:24px;padding:32px 0 28px}
.B .fg h4{margin:0 0 10px}
.B .fg a{display:block;font-size:13.5px;line-height:26px;color:var(--ink2)}
.B .fg p{margin:10px 0 0;font-size:13px;line-height:19px;color:var(--muted)}
.B .fb{border-top:1px solid var(--line);height:48px;display:flex;align-items:center;justify-content:space-between;font-size:12px;color:var(--muted)}
.B .crumbs{display:flex;gap:6px;align-items:center;font-size:12.5px;color:var(--muted);margin:16px 0 10px}
/* каталог */
.B .cl{display:grid;grid-template-columns:272px 1fr;gap:24px;margin-top:16px}
.B .facet{border:1px solid var(--line);border-radius:4px}
.B .facet .g{padding:12px 14px 14px;border-top:1px solid var(--line)}
.B .facet .g:first-child{border-top:0}
.B .facet h5{margin:0 0 8px;display:flex;justify-content:space-between;align-items:center;font-size:13px;font-weight:600}
.B .ck{display:flex;align-items:center;gap:9px;height:28px;font-size:13.5px;color:var(--ink2)}
.B .ck i{width:16px;height:16px;border:1.5px solid #AEB5BD;border-radius:3px;display:flex;align-items:center;justify-content:center;color:#fff;flex:none}
.B .ck.on i{background:var(--ink);border-color:var(--ink)}
.B .ck em{margin-left:auto;font-style:normal;font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--muted)}
.B .ck.dis{color:#A9B0B8}
.B .rg{display:flex;gap:6px}.B .rg span{flex:1;height:34px;border:1px solid #C9CED4;border-radius:4px;display:flex;align-items:center;padding:0 10px;font-family:'IBM Plex Mono',monospace;font-size:12.5px;color:var(--ink2)}
.B .tbar{display:flex;align-items:center;justify-content:space-between;height:44px;border:1px solid var(--line);border-radius:4px;padding:0 6px 0 14px;margin-bottom:12px}
.B .tbar .l{display:flex;gap:6px;align-items:center;font-size:13px;color:var(--muted)}
.B .tag{height:26px;padding:0 6px 0 9px;border-radius:3px;background:var(--bg);display:inline-flex;align-items:center;gap:4px;font-size:12.5px;color:var(--ink)}
.B .tbar .r{display:flex;gap:10px;align-items:center;font-size:13px;color:var(--ink2)}
.B .dd{height:32px;border:1px solid #C9CED4;border-radius:4px;padding:0 10px;display:flex;align-items:center;gap:6px}
.B .pgn{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:13px;color:var(--muted)}
.B .pgn .p{display:flex;gap:4px}.B .pgn .p span{min-width:32px;height:32px;border:1px solid var(--line);border-radius:4px;display:flex;align-items:center;justify-content:center;font-family:'IBM Plex Mono',monospace;font-size:12.5px;color:var(--ink2)}.B .pgn .p span.on{background:var(--ink);color:#fff;border-color:var(--ink)}
.B tr.dim td{color:var(--muted)}.B tr.dim td.im img{opacity:.55}
/* товар */
.B .ph1{display:flex;justify-content:space-between;align-items:flex-start;gap:24px}
.B .ph1 .codes{margin-top:8px;display:flex;gap:20px;font-size:12.5px;color:var(--muted)}
.B .ph1 .codes b{font-family:'IBM Plex Mono',monospace;font-weight:500;color:var(--ink2)}
.B .tools{display:flex;gap:6px}
.B .tb{height:36px;padding:0 10px;border:1px solid #C9CED4;border-radius:4px;display:flex;align-items:center;gap:6px;font-size:13px;color:var(--ink2)}
.B .p3{display:grid;grid-template-columns:400px 1fr 352px;gap:24px;margin-top:20px;align-items:start}
.B .gal .m{height:320px;border:1px solid var(--line);border-radius:4px;display:flex;align-items:center;justify-content:center;padding:24px}
.B .gal .m img{max-height:220px;object-fit:contain}
.B .gal .th{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:8px}
.B .gal .th span{height:64px;border:1px solid var(--line);border-radius:4px;display:flex;align-items:center;justify-content:center;padding:4px;overflow:hidden;font-size:11.5px;color:var(--muted);text-align:center}
.B .gal .th span.on{border-color:var(--ink);border-width:1.5px}.B .gal .th img{max-height:54px;object-fit:contain}
.B .gal .src{font-size:11.5px;color:var(--muted);margin-top:8px}
.B .kv{border:1px solid var(--line);border-radius:4px}
.B .kv .r{display:grid;grid-template-columns:200px 1fr;min-height:38px;align-items:center;padding:0 14px;border-top:1px solid var(--line2);font-size:13.5px}
.B .kv .r:first-child{border-top:0}
.B .kv .r span{color:var(--muted)}
.B .kv .r .mono{font-size:12.5px}
.B .fitbox{margin-top:12px;border:1px solid var(--line);border-radius:4px;padding:12px 14px;font-size:13px;line-height:19px;color:var(--ink2)}
.B .fitbox .h{display:flex;justify-content:space-between;margin-bottom:6px}
.B .buy{border:1px solid #C9CED4;border-radius:4px;overflow:hidden}
.B .buy .p{padding:16px 18px 14px;border-bottom:1px solid var(--line)}
.B .buy .p b{font-size:30px;line-height:36px;font-weight:600;letter-spacing:-.01em}
.B .buy .p small{display:block;font-size:12px;color:var(--muted)}
.B .whs{padding:4px 18px 10px;border-bottom:1px solid var(--line)}
.B .whs .r{display:flex;justify-content:space-between;align-items:center;height:32px;font-size:13px;border-top:1px dashed var(--line)}
.B .whs .r:first-of-type{border-top:0}
.B .whs .lbl{display:block;padding:10px 0 4px}
.B .bq{padding:14px 18px 16px}
.B .bq .row{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.B .bq .q{height:44px}.B .bq .q span{width:40px;height:42px}.B .bq .q i{width:36px;font-size:15px}
.B .bq .tot{text-align:right;font-size:12px;color:var(--muted);line-height:15px}.B .bq .tot b{display:block;font-size:19px;line-height:24px;color:var(--ink);font-weight:600}
.B .bq .btn{width:100%;height:46px;font-size:15px}
.B .bq .btn+.btn{margin-top:8px;height:42px}
.B .bq .ln{display:flex;justify-content:space-between;margin-top:12px;font-size:12.5px;color:var(--ink2)}
.B .dlv{border-top:1px solid var(--line);padding:12px 18px;font-size:12.5px;line-height:18px;color:var(--ink2);display:grid;gap:6px}
.B .dlv div{display:flex;gap:8px}.B .dlv .ico{color:var(--muted);flex:none}
.B .anch{margin-top:32px;border-bottom:1px solid var(--line);display:flex;gap:0;position:relative}
.B .anch span{height:42px;display:flex;align-items:center;padding:0 16px;font-size:13.5px;color:var(--ink2);border-bottom:2px solid transparent}
.B .anch span:first-child{padding-left:0}
.B .anch span.on{color:var(--ink);font-weight:600;border-color:var(--red)}
.B .anch em{font-style:normal;font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--muted);margin-left:5px}
.B .sec{display:grid;grid-template-columns:240px 1fr;gap:24px;padding:22px 0;border-bottom:1px solid var(--line2)}
.B .sec .h2{font-weight:600;font-size:15px}
.B .sec .h2 small{display:block;font-weight:400;color:var(--muted);font-size:12.5px;margin-top:4px}
.B .ctab td{height:40px;font-size:13.5px}
.B .ctab td:first-child{width:180px;color:var(--muted)}
.B .ctab .mods{display:flex;flex-wrap:wrap;gap:4px 14px;font-family:'IBM Plex Mono',monospace;font-size:12.5px;color:var(--ink)}
.B .twocol{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.B .twocol div{font-size:13px;line-height:19px;color:var(--ink2)}
.B .twocol b{display:block;color:var(--ink);font-size:13.5px;margin-bottom:4px}
.B .note{font-size:12px;color:var(--muted);margin-top:8px}
/* мобильная */
.B.m{font-size:14px;line-height:20px}
.B .mh{height:52px;display:flex;align-items:center;justify-content:space-between;padding:0 4px 0 16px}
.B .mh .logo b{font-size:15px}
.B .mh .r{display:flex}
.B .i44{width:44px;height:44px;display:flex;align-items:center;justify-content:center;position:relative}
.B .i44 .n{position:absolute;top:6px;right:4px;font:500 10.5px/16px 'IBM Plex Mono',monospace;background:var(--red);color:#fff;border-radius:3px;padding:0 4px}
.B .ms{margin:0 16px;height:44px;border:1px solid #C9CED4;border-radius:4px;display:flex;align-items:center;gap:8px;padding:0 12px;color:var(--muted)}
.B .mfind{margin:12px 16px 0;border:1px solid var(--line);border-radius:4px;padding:14px}
.B .mfind .g{display:grid;gap:8px;margin-top:10px}
.B .mfind .btn{height:44px}
.B .mlist{margin:16px 0 0;border-top:1px solid var(--line)}
.B .mlist .h{padding:14px 16px 8px}
.B .mlist a{display:flex;align-items:center;justify-content:space-between;height:52px;padding:0 12px 0 16px;border-bottom:1px solid var(--line2);font-size:14.5px}
.B .mlist a span{display:flex;align-items:center;gap:12px}
.B .mlist a em{font-style:normal;font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--muted);display:flex;align-items:center;gap:6px}
.B .mbr{padding:14px 16px 4px}
.B .mbr .l{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.B .mbr .l span{height:36px;padding:0 12px;border:1px solid var(--line);border-radius:4px;display:flex;align-items:center;font-size:13.5px;font-weight:500}
.B .mrows{margin-top:8px;border-top:1px solid var(--line)}
.B .mrow{display:grid;grid-template-columns:64px 1fr;gap:12px;padding:12px 16px;border-bottom:1px solid var(--line2)}
.B .mrow img{width:64px;height:48px;object-fit:contain}
.B .mrow .nm{font-weight:500;font-size:14px;line-height:19px}
.B .mrow .ft{font-size:12px;line-height:16px;color:var(--muted);margin-top:2px}
.B .mrow .b{display:flex;justify-content:space-between;align-items:center;margin-top:8px}
.B .mrow .b b{font-size:15.5px}
.B .mrow .btn{height:40px;padding:0 14px}
.B .mfoot{padding:20px 16px 90px;background:var(--bg);margin-top:20px;font-size:12.5px;color:var(--muted);line-height:19px}
.B .mfoot b{display:block;font-size:16px;color:var(--ink)}
.B .mtab{position:absolute;left:0;right:0;bottom:0;height:60px;border-top:1px solid var(--line);background:#fff;display:grid;grid-template-columns:repeat(5,1fr)}
.B .mtab span{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;font-size:10.5px;color:var(--muted);position:relative}
.B .mtab span.on{color:var(--ink)}
.B .mtab span.on:before{content:"";position:absolute;top:0;left:30%;right:30%;height:2px;background:var(--red)}
.B .mtop{height:48px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);padding:0 4px}
.B .mtop .t{font-size:13px;color:var(--muted)}
.B .mp{padding:12px 16px 0}
.B .mp h1{font-size:19px;line-height:25px;margin:0 0 6px}
.B .mp .codes{font-size:12px;line-height:17px;color:var(--muted)}.B .mp .codes b{font-family:'IBM Plex Mono',monospace;font-weight:500;color:var(--ink2)}
.B .mgal{display:grid;grid-template-columns:1fr 64px;gap:8px;margin:12px 16px 0}
.B .mgal .m{height:180px;border:1px solid var(--line);border-radius:4px;display:flex;align-items:center;justify-content:center}
.B .mgal .m img{max-height:130px;max-width:250px;object-fit:contain}
.B .mgal .th{display:grid;gap:8px}.B .mgal .th span{height:54px;border:1px solid var(--line);border-radius:4px;display:flex;align-items:center;justify-content:center;overflow:hidden}.B .mgal .th span.on{border-color:var(--ink)}.B .mgal .th img{max-height:44px;object-fit:contain}
.B .mkv{margin:12px 16px 0;border:1px solid var(--line);border-radius:4px}
.B .mkv .r{display:grid;grid-template-columns:120px 1fr;min-height:34px;align-items:center;padding:0 12px;border-top:1px solid var(--line2);font-size:13px}
.B .mkv .r:first-child{border-top:0}.B .mkv .r span{color:var(--muted)}
.B .mbuy{margin:12px 16px 0;border:1px solid #C9CED4;border-radius:4px;padding:12px}
.B .mbuy .p{display:flex;justify-content:space-between;align-items:baseline}
.B .mbuy .p b{font-size:26px;line-height:32px}
.B .mbuy .w{font-size:12.5px;line-height:18px;margin:8px 0 10px;color:var(--ink2)}
.B .mbuy .w div{display:flex;justify-content:space-between}
.B .mbuy .row{display:flex;gap:8px}
.B .mbuy .q{height:46px}.B .mbuy .q span{width:38px;height:44px}.B .mbuy .q i{width:28px}
.B .mbuy .row .btn{flex:1;height:46px}
.B .mbuy .one{width:100%;height:44px;margin-top:8px}
.B .msect{border-top:1px solid var(--line)}
.B .msect .hh{height:52px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;font-weight:600;font-size:15px}
.B .msect .hh em{font-style:normal;font-family:'IBM Plex Mono',monospace;font-size:12px;color:var(--muted);font-weight:400;margin-left:6px}
.B .msect .bd{padding:0 16px 16px}
.B .sbar{position:absolute;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #C9CED4;padding:8px 12px 12px;display:grid;grid-template-columns:1fr auto auto;gap:8px;align-items:center}
.B .sbar .p{font-size:11.5px;line-height:14px;color:var(--muted)}.B .sbar .p b{display:block;font-size:17px;line-height:21px;color:var(--ink)}
.B .sbar .q{height:44px}.B .sbar .q span{width:34px;height:42px}.B .sbar .q i{width:24px}
.B .sbar .btn{height:44px}
`;

const CI = {
  laser: '<path d="M3.5 9.5h17v6h-17zM6 15.5V18h12v-2.5M7 12.5h4"/>',
  ink: '<path d="M6 6h4v12H6zM14 6h4v12h-4zM6 9h4M14 9h4"/>',
  screens: '<path d="M3.5 5h17M5 5v10h14V5M12 15v5M9 20h6"/>',
  photo: '<rect x="4" y="5" width="16" height="14" rx="1"/><path d="m4 16 5-5 4 4 2-2 5 5"/>',
  roll: '<ellipse cx="7" cy="12" rx="3" ry="5"/><path d="M7 7h11v10H7M7 12h.01"/>',
  mounts: '<path d="M8 3.5h8M12 3.5V13M8 13h8l2 5H6z"/>',
};
const ci = (id, s = 20) => `<svg class="ci" width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${CI[id]}</svg>`;
// Количество товаров по категориям в макете не показываем как факт: метка «демо»
const CNT = { laser: '1 240', ink: '410', screens: '86', photo: '52', roll: '38', mounts: '24' };

function header() {
  return `<div class="rl"></div><header class="hdr"><div class="wrap"><a class="logo" href="B-home.html"><b><i></i>SAKURA RUSSIA</b><small>Картриджи · бумага · экраны</small></a>
<div class="search"><span class="scope">Везде ${icon('down', 14)}</span><span class="q">${icon('search', 17)}Артикул, номер оригинала или модель</span><span class="go">Найти</span></div>
<div class="ph"><b>${STORE.phone}</b><small>${STORE.hours}</small></div>
<div class="acts"><a class="act">${icon('heart', 18)}Избранное <span class="n">3</span></a><a class="act">${icon('compare', 18)}Сравнение <span class="n">2</span></a><a class="act">${icon('cart', 18)}Корзина <span class="n r">2</span></a><a class="act">${icon('user', 18)}Вход</a></div></div></header>
<div class="bar2"><div class="wrap"><a>${icon('menu', 16)}&nbsp; Каталог</a>${CATEGORIES.map(c => `<a ${c.id === 'laser' ? 'href="B-catalog.html"' : ''}>${c.name}</a>`).join('')}<a class="r">${icon('printer', 16)}Подбор по принтеру</a><a class="r2">Доставка</a><a class="r2">Контакты</a></div></div>`;
}
function footer() {
  return `<footer class="ftr"><div class="wrap"><div class="fg"><div><a class="logo"><b><i></i>SAKURA RUSSIA</b><small>Картриджи · бумага · экраны</small></a><p>Розничный интернет-магазин расходных материалов для печати и проекционного оборудования.</p></div>
<div><h4 class="lbl">Каталог</h4>${CATEGORIES.map(c => `<a>${c.name}</a>`).join('')}</div>
<div><h4 class="lbl">Покупателям</h4><a>Подбор картриджа</a><a>Доставка и оплата</a><a>Гарантия и возврат</a><a>Помощь и FAQ</a><a>Личный кабинет</a></div>
<div><h4 class="lbl">Компания</h4><a>О компании</a><a>Контакты</a><a>Реквизиты</a><a>Политика конфиденциальности</a><a>Пользовательское соглашение</a></div>
<div><h4 class="lbl">Контакты</h4><a><b>${STORE.phone}</b></a><a>${STORE.email}</a><p>${STORE.address}<br>${STORE.hours}</p></div></div>
<div class="fb"><span>© 2026 Sakura Russia · Реквизиты продавца указываются после согласования</span><span class="mono">Демо-данные: цены, остатки и сроки условные</span></div></div></footer>`;
}
const q = (n = 1) => `<span class="q"><span>${icon('minus', 14)}</span><i>${n}</i><span>${icon('plus', 14)}</span></span>`;
function row(p, compact = false) {
  const act = p.stock === 'in' ? `${q()}<a class="btn s">${icon('cart', 15)}В корзину</a>` : p.stock === 'order' ? `${q()}<a class="btn s o">Заказать</a>` : `<a class="btn s o">Сообщить о поступлении</a>`;
  if (compact) return `<tr class="${p.stock === 'out' ? 'dim' : ''}"><td class="im"><img src="${asset(`products/${p.img}.jpg`)}" alt=""></td><td><a class="nm" href="B-product.html">${esc(p.name)}</a><div class="ft"><span class="clr"><i style="background:${colorDot[p.color]}"></i>${p.color}</span> · ${esc(p.fits)}</div></td><td class="n">${num(p.yield)}</td><td><span class="stk ${p.stock}"><i></i>${stockLabel[p.stock]}</span></td><td class="pr">${rub(p.price)}</td><td class="act"><div class="w">${act}</div></td></tr>`;
  return `<tr class="${p.stock === 'out' ? 'dim' : ''}"><td class="im"><img src="${asset(`products/${p.img}.jpg`)}" alt=""></td><td><a class="nm" href="B-product.html">${esc(p.name)}</a><div class="ft">${esc(p.fits)}</div></td><td class="c">${p.code}</td><td class="n">${num(p.yield)}</td><td><span class="clr"><i style="background:${colorDot[p.color]}"></i>${p.color}</span></td><td><span class="stk ${p.stock}"><i></i>${stockLabel[p.stock]}</span></td><td class="pr">${rub(p.price)}</td><td class="act"><div class="w">${act}</div></td></tr>`;
}
const THEAD_C = `<tr><th></th><th>Наименование и совместимость</th><th style="text-align:right">Ресурс</th><th>Наличие</th><th style="text-align:right">Цена</th><th></th></tr>`;
const THEAD = `<tr><th></th><th>Наименование и совместимость</th><th>Код</th><th style="text-align:right">Ресурс, стр.</th><th>Цвет</th><th>Наличие</th><th style="text-align:right">Цена</th><th></th></tr>`;

export function home() {
  const body = `<div class="screen B" data-name="B / 01 Главная / Desktop 1440" style="width:1440px">${header()}<main class="wrap">
<section class="top3"><nav class="tree"><div class="h"><span class="lbl">Каталог</span><span class="lbl">демо</span></div>${CATEGORIES.map((c, i) => `<a class="${i === 0 ? 'on' : ''}"><span>${ci(c.id)}${c.name}</span><em>${CNT[c.id]}</em></a>${i === 0 ? '<a class="sub">Для HP <em>420</em></a><a class="sub">Для Canon <em>260</em></a><a class="sub">Для Kyocera <em>180</em></a><a class="sub">Все производители</a>' : ''}`).join('')}</nav>
<div class="lead"><span class="lbl">Подбор картриджа</span><h1>Найдите картридж по модели принтера или номеру оригинала</h1><p>Выберите производителя и модель. В выдаче останутся только совместимые позиции с ценой и наличием.</p>
<div class="sel3"><div class="f"><div class="v"><label>Производитель</label><span class="t">HP</span></div>${icon('down', 16)}</div><div class="f"><div class="v"><label>Модель или серия</label><span class="t p">LaserJet P1102</span></div>${icon('down', 16)}</div><div class="f"><div class="v"><label>Тип</label><span class="t">Лазерный</span></div>${icon('down', 16)}</div><a class="btn k">Подобрать</a></div>
<div class="hint">Часто ищут: <b>CE285A</b><b>TK-1170</b><b>CF226X</b><b>W1106A</b><b>Canon 725</b></div>
<div class="kpi"><div><b>Код на корпусе</b>Модель указана на передней панели или наклейке снизу</div><div><b>Номер оригинала</b>Например, HP 85A или CE285A на старом картридже</div><div><b>Нет в списке</b>Отправьте запрос, менеджер проверит совместимость</div></div></div>
<aside class="info"><div class="row">${icon('truck', 20)}<span><b>Доставка по России</b>СДЭК: до двери или пункта выдачи, расчёт при оформлении</span></div><div class="row">${icon('pin', 20)}<span><b>Самовывоз в Москве</b>${STORE.address}</span></div><div class="row">${icon('card', 20)}<span><b>Оплата онлайн</b>Банковская карта или СБП</span></div><div class="row">${icon('file', 20)}<span><b>Чеки и документы</b>Электронный чек после оплаты</span></div><div class="row">${icon('phone', 20)}<span><b>${STORE.phone}</b>${STORE.hours}</span></div></aside></section>
<section class="brandidx"><div class="h"><span class="lbl">Картриджи для</span></div><div class="l">${PRINTER_BRANDS.slice(0, 12).map((b, i) => `<a>${b}<em>${[420, 260, 140, 180, 120, 160, 90, 70, 40, 30, 35, 210, 25][i]}</em></a>`).join('')}</div></section>
<div class="shead"><div class="l"><h2>Популярные позиции</h2><span class="lbl">12 из 1 240 · демо</span></div><div class="seg"><span class="on">Лазерные</span><span>Струйные</span><span>Бумага</span><span>Экраны</span></div></div>
<div class="tbl"><table>${THEAD}${['ce285a', 'tk1170', 'w1106a', 'cb435a', 'cb540a', 'cb541a', 'tk1200', 'cb543a'].map(id => row(P[id])).join('')}</table></div>
<div class="shead"><div class="l"><h2>Разделы каталога</h2></div><a class="lnk">Весь каталог ${icon('arrow', 15)}</a></div>
<section class="cats6">${CATEGORIES.map(c => `<a>${ci(c.id, 28)}<b>${c.name}</b><small>${c.note}</small><em>${CNT[c.id]} поз. · демо</em></a>`).join('')}</section>
<section class="help"><div><h2>Не нашли нужную позицию?</h2><p>Пришлите модель принтера, номер оригинала или фото старого картриджа. Ответим в рабочее время.</p><a class="btn">Отправить запрос</a></div><div><h2>Как подобрать картридж</h2><ol style="margin-top:12px"><li>Найдите модель принтера на корпусе или наклейке</li><li>Введите модель или номер оригинала в поиск</li><li>Сверьте таблицу совместимости в карточке товара</li></ol></div></section>
</main>${footer()}</div>`;
  return page({ title: 'B / Главная', families: FAM, css: CSS, body, width: 1440 });
}

export function catalog() {
  const ck = (t, n, on, dis) => `<div class="ck ${on ? 'on' : ''} ${dis ? 'dis' : ''}"><i>${on ? icon('check', 12, 2.6) : ''}</i>${t}<em>${n}</em></div>`;
  const body = `<div class="screen B" data-name="B / 02 Каталог / Desktop 1440" style="width:1440px">${header()}<main class="wrap">
<div class="crumbs">Главная ${icon('right', 13)} Каталог ${icon('right', 13)} Лазерные картриджи ${icon('right', 13)} <span style="color:var(--ink)">HP</span></div>
<div style="display:flex;align-items:baseline;gap:14px"><h1>Лазерные картриджи для HP</h1><span class="lbl">12 позиций · демо</span></div>
<div class="cl"><aside class="facet">
<div class="g"><h5>Наличие</h5>${ck('В наличии', 10, true)}${ck('Под заказ', 1)}${ck('Нет в наличии', 1)}</div>
<div class="g"><h5>Серия принтера ${icon('down', 14)}</h5>${ck('LaserJet P1000–P1600', 4, true)}${ck('LaserJet 1000–3300', 1)}${ck('Color LaserJet CP1215–1518', 4)}${ck('Color LaserJet CP4005', 1)}${ck('Laser 107–137', 1)}</div>
<div class="g"><h5>Модель оригинала ${icon('down', 14)}</h5>${ck('HP 85A', 1)}${ck('HP 35A', 2)}${ck('HP 36A', 2)}${ck('HP 125A', 4)}${ck('HP 106A', 1)}</div>
<div class="g"><h5>Цвет</h5>${ck('Чёрный', 7)}${ck('Голубой', 1)}${ck('Пурпурный', 1)}${ck('Жёлтый', 1)}</div>
<div class="g"><h5>Ресурс, стр.</h5><div class="rg"><span>1 000</span><span>7 500</span></div></div>
<div class="g"><h5>Цена, ₽</h5><div class="rg"><span>413</span><span>5 693</span></div></div>
<div class="g"><a class="btn k" style="width:100%">Показать 4 позиции</a></div></aside>
<section><div class="tbar"><div class="l">Фильтры: <span class="tag">HP ${icon('close', 12)}</span><span class="tag">В наличии ${icon('close', 12)}</span><span class="tag">LaserJet P1000–P1600 ${icon('close', 12)}</span><a class="lnk" style="margin-left:4px">Сбросить</a></div>
<div class="r">Сортировка <span class="dd">Цена: по возрастанию ${icon('down', 14)}</span><div class="seg"><span class="on">${icon('list', 16)}&nbsp;Таблица</span><span>${icon('grid', 16)}&nbsp;Плитка</span></div></div></div>
<div class="tbl"><table>${THEAD_C}${['cb435a', 'ce285a', 'cb436a', 'w1106a', 'c7115a', 'cb541a', 'cb542a', 'cb540a', 'cb543a', 'cb400a'].map(id => row(P[id], true)).join('')}</table></div>
<div class="pgn"><span>Показаны 1–10 из 12</span><div class="p"><span class="on">1</span><span>2</span><span>${icon('right', 14)}</span></div><span>По 10 · 25 · 50</span></div>
</section></div></main>${footer()}</div>`;
  return page({ title: 'B / Каталог', families: FAM, css: CSS, body, width: 1440 });
}

export function product() {
  const p = P.ce285a;
  const body = `<div class="screen B" data-name="B / 03 Товар CE285A / Desktop 1440" style="width:1440px">${header()}<main class="wrap">
<div class="crumbs">Главная ${icon('right', 13)} Лазерные картриджи ${icon('right', 13)} HP ${icon('right', 13)} <span style="color:var(--ink)">CE285A</span></div>
<div class="ph1"><div><h1>${esc(p.name)}</h1><div class="codes"><span>Артикул <b>${p.sku}</b></span><span>Код производителя <b>${p.mfr}</b></span><span>Аналог <b>${p.oem}</b></span></div></div>
<div class="tools"><span class="tb">${icon('heart', 16)}В избранное</span><span class="tb">${icon('compare', 16)}Сравнить</span><span class="tb">${icon('share', 16)}</span></div></div>
<section class="p3"><div class="gal"><div class="m"><img src="${asset('products/ce285a.jpg')}" alt=""></div><div class="th"><span class="on"><img src="${asset('products/ce285a.jpg')}" alt=""></span><span><img src="${asset('products/ce285a-label.jpg')}" alt=""></span><span><img src="${asset('products/ce285a-end.jpg')}" alt=""></span><span>Схема установки<br>PDF</span></div><div class="src">Фото упаковки: sakurarussia.ru</div></div>
<div><div class="kv">${[['Тип', 'Лазерный картридж'], ['Цвет печати', '<span class="clr"><i style="background:#1d1d1f"></i>Чёрный</span>'], ['Ресурс', '2 000 стр. при 5% заполнении'], ['Модель оригинала', 'HP 85A, HP 35A, HP 36A, Canon 725'], ['Совместимость', '17 моделей HP и Canon']].map(([k, v]) => `<div class="r"><span>${k}</span>${v}</div>`).join('')}</div>
<div class="fitbox"><div class="h"><b>Подходит для</b><a class="lnk">Таблица совместимости ${icon('down', 14)}</a></div><span class="mono" style="font-size:12.5px">HP LaserJet P1005 · P1006 · P1102 · P1102W · P1505 · M1120 · M1522N … Canon LBP6000 · MF3010</span></div></div>
<aside class="buy"><div class="p"><b>${rub(p.price)}</b><small>Цена за 1 шт., включая НДС · демо</small></div>
<div class="whs"><span class="lbl">Наличие по складам</span><div class="r"><span>Москва, ${STORE.addressShort}</span><span class="stk"><i></i>В наличии</span></div><div class="r"><span>Склад поставщика</span><span class="stk order"><i></i>Под заказ</span></div></div>
<div class="bq"><div class="row">${q(2)}<div class="tot">2 × 425 ₽<b>850 ₽</b></div></div><a class="btn">${icon('cart', 18)}В корзину</a><a class="btn o">Купить в 1 клик</a><div class="ln"><span>${icon('info', 14)} Сверьте модель принтера</span><a class="lnk">Таблица ${icon('arrow', 13)}</a></div></div>
<div class="dlv"><div>${icon('truck', 16)}Доставка СДЭК: стоимость и срок при оформлении</div><div>${icon('pin', 16)}Самовывоз: ${STORE.addressShort}, ${STORE.hours.toLowerCase()}</div><div>${icon('card', 16)}Оплата картой или через СБП</div></div></aside></section>
<nav class="anch"><span class="on">Совместимость <em>17</em></span><span>Характеристики</span><span>Описание</span><span>Доставка и оплата</span><span>Гарантия и возврат</span><span>Документы <em>1</em></span><span>Аналоги <em>3</em></span></nav>
<section class="sec"><div class="h2">Совместимость<small>По названию товара в текущем магазине, требует проверки</small></div><div><div class="tbl"><table class="ctab"><tr><th>Серия</th><th>Модели</th></tr>${Object.entries(CE285A_COMPAT).map(([s, ms]) => `<tr><td>${s}</td><td><div class="mods">${ms.map(m => `<span>${m}</span>`).join('')}</div></td></tr>`).join('')}</table></div></div></section>
<section class="sec"><div class="h2">Характеристики</div><div class="kv">${CE285A_SPECS.map(([k, v]) => `<div class="r"><span>${k}</span>${k.startsWith('Артикул') ? `<span class="mono" style="color:var(--ink)">${v}</span>` : v}</div>`).join('')}</div></section>
<section class="sec"><div class="h2">Описание</div><div style="color:var(--ink2);max-width:820px">Совместимый картридж SAKURA для лазерных принтеров HP и Canon. Чёрная печать, ресурс до 2&nbsp;000 страниц при 5% заполнении. Перед заказом проверьте модель принтера в таблице совместимости.</div></section>
<section class="sec"><div class="h2">Доставка, оплата, гарантия</div><div class="twocol"><div><b>Доставка</b>СДЭК до двери или пункта выдачи. Самовывоз в Москве. Стоимость рассчитывается при оформлении.</div><div><b>Оплата</b>Банковская карта или СБП онлайн. Электронный чек после оплаты.</div><div><b>Гарантия и возврат</b>Порядок описан на странице «Гарантия и возврат». Срок гарантии подтверждает продавец.</div></div></section>
<div class="shead"><div class="l"><h2>Аналоги и похожие позиции</h2></div></div>
<div class="tbl"><table>${THEAD}${['cb435a', 'cb436a', 'c7115a'].map(id => row(P[id])).join('')}</table></div>
</main>${footer()}</div>`;
  return page({ title: 'B / Товар', families: FAM, css: CSS, body, width: 1440 });
}

export function mHome() {
  const mrow = p => `<div class="mrow"><img src="${asset(`products/${p.img}.jpg`)}" alt=""><div><div class="nm">${esc(p.name)}</div><div class="ft"><span class="mono">${p.code}</span> · ${num(p.yield)} стр. · ${p.color}</div><div class="b"><b>${rub(p.price)}</b><a class="btn">${icon('cart', 16)}В корзину</a></div></div></div>`;
  const body = `<div class="screen B m" data-name="B / 04 Главная / Mobile 390" style="width:390px"><div class="rl"></div>
<div class="mh"><a class="logo"><b><i></i>SAKURA RUSSIA</b></a><div class="r"><span class="i44">${icon('phone', 20)}</span><span class="i44">${icon('cart', 21)}<b class="n">2</b></span></div></div>
<div class="ms">${icon('search', 18)}Артикул или модель принтера</div>
<section class="mfind"><span class="lbl">Подбор картриджа</span><div class="g"><div class="f"><div class="v"><label>Производитель</label><span class="t">HP</span></div>${icon('down', 16)}</div><div class="f"><div class="v"><label>Модель или серия</label><span class="t p">LaserJet P1102</span></div>${icon('down', 16)}</div><a class="btn k">Подобрать</a></div></section>
<section class="mlist"><div class="h"><span class="lbl">Каталог · демо</span></div>${CATEGORIES.map(c => `<a><span>${ci(c.id, 22)}${c.name}</span><em>${CNT[c.id]}${icon('right', 16)}</em></a>`).join('')}</section>
<section class="mbr"><span class="lbl">Картриджи для</span><div class="l">${PRINTER_BRANDS.slice(0, 8).map(b => `<span>${b}</span>`).join('')}<span>Ещё 5</span></div></section>
<section class="mrows"><div style="padding:14px 16px 4px;display:flex;justify-content:space-between"><b style="font-size:16px">Популярные позиции</b><a class="lnk">Все</a></div>${['ce285a', 'tk1170', 'w1106a', 'cb435a'].map(id => mrow(P[id])).join('')}</section>
<div class="mfoot"><b>${STORE.phone}</b>${STORE.hours} · ${STORE.address}<br>Доставка и оплата · Гарантия и возврат · Контакты · Помощь<br><br><span class="mono">Демо-данные</span></div>
<nav class="mtab"><span class="on">${icon('home', 21)}Главная</span><span>${icon('menu', 21)}Каталог</span><span>${icon('search', 21)}Поиск</span><span>${icon('cart', 21)}Корзина</span><span>${icon('user', 21)}Кабинет</span></nav></div>`;
  return page({ title: 'B / Mobile главная', families: FAM, css: CSS, body, width: 390 });
}

export function mProduct() {
  const p = P.ce285a;
  const body = `<div class="screen B m" data-name="B / 05 Товар: первый экран / Mobile 390" style="width:390px;height:844px"><div class="rl"></div>
<div class="mtop"><span class="i44">${icon('left', 21)}</span><span class="t">Лазерные картриджи · HP</span><div style="display:flex"><span class="i44">${icon('heart', 20)}</span><span class="i44">${icon('cart', 20)}<b class="n">2</b></span></div></div>
<div class="mp"><h1>${esc(p.name)}</h1><div class="codes">Артикул <b>${p.sku}</b><br>Аналог <b>${p.oem}</b></div></div>
<div class="mgal"><div class="m"><img src="${asset('products/ce285a.jpg')}" alt=""></div><div class="th"><span class="on"><img src="${asset('products/ce285a.jpg')}" alt=""></span><span><img src="${asset('products/ce285a-label.jpg')}" alt=""></span><span><img src="${asset('products/ce285a-end.jpg')}" alt=""></span></div></div>
<div class="mkv">${[['Ресурс', '2 000 стр.'], ['Цвет', '<span class="clr"><i style="background:#1d1d1f"></i>Чёрный</span>'], ['Совместимость', '17 моделей HP, Canon']].map(([k, v]) => `<div class="r"><span>${k}</span>${v}</div>`).join('')}</div>
<section class="mbuy"><div class="p"><b>${rub(p.price)}</b><span class="lbl">за 1 шт.</span></div><div class="w"><div><span>Москва, ${STORE.addressShort}</span><span class="stk"><i></i>В наличии</span></div><div><span>Склад поставщика</span><span class="stk order"><i></i>Под заказ</span></div></div>
<div class="row">${q(2)}<a class="btn">В корзину · 850 ₽</a></div><a class="btn o one">Купить в 1 клик</a></section></div>`;
  return page({ title: 'B / Mobile товар', families: FAM, css: CSS, body, width: 390 });
}

export function mProductScroll() {
  const body = `<div class="screen B m" data-name="B / 06 Товар: разделы и панель покупки / Mobile 390" style="width:390px;height:844px"><div class="rl"></div>
<div class="mtop"><span class="i44">${icon('left', 21)}</span><span class="t">CE285A / CB435A / 436A / 725</span><span class="i44">${icon('share', 20)}</span></div>
<div class="msect"><div class="hh"><span>Совместимость<em>17</em></span>${icon('minus', 18)}</div><div class="bd"><div class="ms" style="margin:0 0 10px">${icon('search', 17)}Найти модель</div>${Object.entries(CE285A_COMPAT).map(([s, ms]) => `<div style="font-size:12.5px;color:var(--muted);margin:8px 0 4px">${s}</div><div class="mono" style="font-size:12.5px;line-height:20px">${ms.join(' · ')}</div>`).join('')}</div></div>
<div class="msect"><div class="hh">Характеристики ${icon('plus', 18)}</div></div>
<div class="msect"><div class="hh">Описание ${icon('plus', 18)}</div></div>
<div class="msect"><div class="hh">Доставка и оплата ${icon('plus', 18)}</div></div>
<div class="msect"><div class="hh">Гарантия и возврат ${icon('plus', 18)}</div></div>
<div class="msect"><div class="hh"><span>Документы<em>1</em></span>${icon('plus', 18)}</div></div>
<div class="msect"><div class="hh"><span>Аналоги<em>3</em></span>${icon('plus', 18)}</div></div>
<div class="sbar"><div class="p">CE285A · 2 шт.<b>850 ₽</b></div>${q(2)}<a class="btn">В корзину</a></div></div>`;
  return page({ title: 'B / Mobile товар, разделы', families: FAM, css: CSS, body, width: 390 });
}
