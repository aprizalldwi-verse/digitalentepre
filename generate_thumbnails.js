const fs = require("fs");
const path = require("path");
const os = require("os");
const { spawn } = require("child_process");

const ROOT = __dirname;
const PORT = 9444;
const PROFILE = path.join(os.tmpdir(), "thumb-profile-" + Date.now());

const CHROME_CANDIDATES = [
    "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe",
    "C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe",
    "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe",
    "C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe"
];

const THEMES = {
    html: ["#E44D26", "#F16529", "#ffffff"],
    css: ["#264DE4", "#2965F1", "#ffffff"],
    js: ["#F7DF1E", "#E9A100", "#241f0a"],
    php: ["#777BB4", "#4F5D95", "#ffffff"],
    mysql: ["#00758F", "#4479A1", "#ffffff"],
    laravel: ["#FF2D20", "#B81710", "#ffffff"],
    api: ["#0EA5E9", "#2563EB", "#ffffff"],
    react: ["#61DAFB", "#0B7285", "#ffffff"],
    node: ["#339933", "#166B16", "#ffffff"],
    figma: ["#A259FF", "#F24E1E", "#ffffff"],
    uiux: ["#6C5CE7", "#0ACF83", "#ffffff"],
    design: ["#EC4899", "#8B5CF6", "#ffffff"],
    photoshop: ["#31A8FF", "#0057B8", "#ffffff"],
    marketing: ["#FF6B6B", "#4ECDC4", "#ffffff"],
    seo: ["#1A73E8", "#0F9D58", "#ffffff"],
    smm: ["#E1306C", "#833AB4", "#ffffff"],
    copywriting: ["#FF8C42", "#FFD166", "#3b2a00"],
    content: ["#E63946", "#457B9D", "#ffffff"],
    canva: ["#00C4CC", "#7D2AE8", "#ffffff"],
    excel: ["#217346", "#185C37", "#ffffff"],
    python: ["#3776AB", "#FFD43B", "#ffffff"],
    data: ["#FF6384", "#36A2EB", "#ffffff"],
    business: ["#2D3436", "#636E72", "#ffffff"],
    entrepreneur: ["#6C5CE7", "#A29BFE", "#ffffff"],
    branding: ["#FD79A8", "#E84393", "#ffffff"],
    web: ["#00B894", "#00CEC9", "#ffffff"],
    mobile: ["#00D2FF", "#3A7BD5", "#ffffff"],
    android: ["#3DDC84", "#127A46", "#ffffff"],
    kotlin: ["#7F52FF", "#3E1F92", "#ffffff"],
    default: ["#4F46E5", "#7C3AED", "#ffffff"]
};

function pickTheme(name) {
    const n = name.toLowerCase();
    const regexRules = [
        [/\bai\b|\bmachine learning\b|\bml\b/, "data"],
        [/react native/, "react"],
        [/\breact\b/, "react"],
        [/frontend|front-end/, "web"],
        [/product design/, "design"],
        [/node\.?js/, "node"],
        [/react native/, "react"],
        [/html/, "html"],
        [/css/, "css"],
        [/javascript|\bjs\b/, "js"],
        [/php/, "php"],
        [/mysql/, "mysql"],
        [/laravel/, "laravel"],
        [/rest|api/, "api"],
        [/figma/, "figma"],
        [/ui.?ux|user research|wireframe/, "uiux"],
        [/photoshop/, "photoshop"],
        [/graphic|canva/, "design"],
        [/branding/, "branding"],
        [/copywriting/, "copywriting"],
        [/seo/, "seo"],
        [/social media|\bsmm\b/, "smm"],
        [/marketing/, "marketing"],
        [/content/, "content"],
        [/excel/, "excel"],
        [/python/, "python"],
        [/\bdata\b/, "data"],
        [/kotlin/, "kotlin"],
        [/android/, "android"],
        [/flutter|mobile/, "mobile"],
        [/entrepreneur/, "entrepreneur"],
        [/bisnis|business/, "business"],
        [/fullstack|full stack/, "web"],
        [/\breact\b/, "react"],
        [/\bnode\b/, "node"]
    ];
    for (const [re, theme] of regexRules) {
        if (re.test(n)) return theme;
    }
    return "default";
}

function categoryLabel(theme) {
    const map = {
        html: "Programming", css: "Programming", js: "Programming",
        php: "Programming", mysql: "Programming", laravel: "Programming",
        api: "Programming", react: "Programming", node: "Programming",
        figma: "Design", uiux: "Design", design: "Design",
        photoshop: "Design", canva: "Design", branding: "Design",
        marketing: "Digital Marketing", seo: "Digital Marketing",
        smm: "Digital Marketing", copywriting: "Digital Marketing",
        content: "Digital Marketing",
        excel: "Data & Produktivitas", python: "Programming",
        data: "Data & Produktivitas",
        business: "Bisnis", entrepreneur: "Bisnis",
        mobile: "Mobile Development", android: "Mobile Development",
        kotlin: "Mobile Development", web: "Web Development",
        default: "E-Learning"
    };
    return map[theme] || "E-Learning";
}

function bootcampLabel(theme) {
    const map = {
        html: "Development", css: "Development", js: "Development",
        php: "Development", mysql: "Development", laravel: "Development",
        api: "Development", react: "Development", node: "Development",
        web: "Development",
        design: "Design", figma: "Design", uiux: "Design",
        photoshop: "Design", canva: "Design", branding: "Design",
        data: "Data & AI", python: "Data & AI",
        mobile: "Mobile Dev", android: "Mobile Dev", kotlin: "Mobile Dev",
        marketing: "Marketing", seo: "Marketing", smm: "Marketing",
        content: "Marketing", copywriting: "Marketing",
        business: "Entrepreneur", entrepreneur: "Entrepreneur",
        default: "Profesional"
    };
    return "Bootcamp " + (map[theme] || "Profesional");
}

function esc(s) {
    return String(s)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function wrap(text, maxChars, maxLines) {
    const words = String(text).split(/\s+/).filter(Boolean);
    const lines = [];
    let current = "";
    for (const w of words) {
        const next = current ? current + " " + w : w;
        if (next.length <= maxChars || !current) {
            current = next;
        } else {
            lines.push(current);
            current = w;
        }
    }
    if (current) lines.push(current);

    if (lines.length > maxLines) {
        const joined = lines.join(" ");
        const per = Math.ceil(joined.length / maxLines);
        const out = [];
        let cur = "";
        for (const w of joined.split(/\s+/)) {
            const n = cur ? cur + " " + w : w;
            if (n.length <= per || !cur) cur = n;
            else { out.push(cur); cur = w; }
        }
        if (cur) out.push(cur);
        return out.slice(0, maxLines);
    }
    return lines;
}

function buildSvg(item) {
    const theme = THEMES[item.theme] || THEMES.default;
    const [c1, c2, textColor] = theme;
    const dim = textColor === "#ffffff" ? "rgba(255,255,255,0.82)" : "rgba(0,0,0,0.68)";
    const faint = textColor === "#ffffff" ? "rgba(255,255,255,0.55)" : "rgba(0,0,0,0.5)";
    const soft = textColor === "#ffffff" ? "rgba(255,255,255,0.10)" : "rgba(0,0,0,0.07)";

    const title = item.title;
    let fontSize = title.length <= 20 ? 46 : title.length <= 34 ? 40 : 34;
    let lines = wrap(title, Math.floor(690 / (fontSize * 0.56)), 2);
    if (lines.length > 2) {
        fontSize = 34;
        lines = wrap(title, Math.floor(690 / (fontSize * 0.56)), 2);
    }

    const lineHeight = Math.round(fontSize * 1.18);
    const blockHeight = (lines.length - 1) * lineHeight;
    const firstBaseline = 232 - blockHeight / 2 + fontSize * 0.34;

    let titleSvg = "";
    lines.forEach(function (line, i) {
        titleSvg +=
            '<text x="56" y="' + Math.round(firstBaseline + i * lineHeight) +
            '" font-family="Arial, Helvetica, sans-serif" font-size="' + fontSize +
            '" font-weight="bold" fill="' + textColor + '">' + esc(line) + "</text>";
    });

    const subtitleY = Math.round(firstBaseline + (lines.length - 1) * lineHeight + 46);

    const badge = esc(item.badge.toUpperCase());
    const badgeWidth = Math.round(badge.length * 9.6 + 40);

    return [
        '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="450" viewBox="0 0 800 450">',
        '<defs>',
        '<linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">',
        '<stop offset="0%" style="stop-color:' + c1 + '"/>',
        '<stop offset="100%" style="stop-color:' + c2 + '"/>',
        "</linearGradient>",
        "</defs>",
        '<rect width="800" height="450" fill="url(#bg)"/>',
        '<circle cx="720" cy="70" r="190" fill="rgba(255,255,255,0.10)"/>',
        '<circle cx="640" cy="392" r="120" fill="rgba(255,255,255,0.07)"/>',
        '<circle cx="726" cy="250" r="66" fill="rgba(255,255,255,0.09)"/>',
        '<rect x="612" y="292" width="132" height="132" rx="26" fill="rgba(255,255,255,0.08)"/>',
        '<rect x="56" y="52" width="' + badgeWidth + '" height="36" rx="18" fill="rgba(255,255,255,0.20)"/>',
        '<text x="' + (56 + badgeWidth / 2) + '" y="76" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="15" font-weight="bold" letter-spacing="1.4" fill="' + textColor + '">' + badge + "</text>",
        titleSvg,
        '<text x="56" y="' + subtitleY + '" font-family="Arial, Helvetica, sans-serif" font-size="19" fill="' + dim + '">' + esc(item.subtitle) + "</text>",
        '<rect x="56" y="' + (subtitleY + 26) + '" width="72" height="6" rx="3" fill="' + (textColor === "#ffffff" ? "rgba(255,255,255,0.9)" : "rgba(0,0,0,0.75)") + '"/>',
        '<text x="56" y="416" font-family="Arial, Helvetica, sans-serif" font-size="15" font-weight="bold" letter-spacing="0.6" fill="' + faint + '">BELAJARYUK</text>',
        '<text x="744" y="416" text-anchor="end" font-family="Arial, Helvetica, sans-serif" font-size="15" fill="' + soft + '">' + esc(item.corner) + "</text>",
        "</svg>"
    ].join("");
}

function readTsv(file) {
    if (!fs.existsSync(file)) return [];
    return fs.readFileSync(file, "utf8").split(/\r?\n/).filter(Boolean).map(function (line) {
        return line.split("\t");
    });
}

function parseOldSvg(file) {
    const svg = fs.readFileSync(file, "utf8");
    const colors = /stop-color:(#[0-9a-fA-F]{3,8})/.exec(svg);
    const texts = [];
    const re = /<text[^>]*font-size="(\d+)"[^>]*>([\s\S]*?)<\/text>/g;
    let m;
    while ((m = re.exec(svg)) !== null) {
        texts.push({ size: parseInt(m[1], 10), text: m[2].replace(/&amp;/g, "&").trim() });
    }
    const titleParts = texts.filter(function (t) { return t.size >= 28; }).map(function (t) { return t.text; });
    const subtitle = (texts.filter(function (t) { return t.size >= 16 && t.size < 28; })[0] || {}).text || "";
    return {
        title: titleParts.join(" ") || path.basename(file, ".png"),
        subtitle: subtitle,
        color: colors ? colors[1] : null
    };
}

function themeFromColor(color, fallbackKey) {
    for (const key of Object.keys(THEMES)) {
        if (THEMES[key][0].toLowerCase() === String(color).toLowerCase()) return key;
    }
    return fallbackKey;
}

const META_FILE = path.join(ROOT, "assets", "thumbnails.json");

function loadMeta() {
    if (!fs.existsSync(META_FILE)) return {};
    try {
        return JSON.parse(fs.readFileSync(META_FILE, "utf8"));
    } catch (e) {
        return {};
    }
}

function saveMeta(meta) {
    fs.writeFileSync(META_FILE, JSON.stringify(meta, null, 2));
}

function queryDb(sql) {
    const candidates = [
        "C:\\xampp\\mysql\\bin\\mysql.exe",
        "mysql"
    ];
    for (const bin of candidates) {
        try {
            const out = require("child_process")
                .execFileSync(bin, ["-uroot", "-N", "-B", "--default-character-set=utf8mb4", "-e", sql], {
                    encoding: "utf8",
                    windowsHide: true
                });
            return out;
        } catch (e) {
            continue;
        }
    }
    return null;
}

function parseTsvBlock(text) {
    const map = {};
    if (!text) return map;
    text.split(/\r?\n/).filter(Boolean).forEach(function (line) {
        const row = line.split("\t");
        if (row.length >= 4) {
            map[path.basename(row[3])] = { title: row[1], badge: row[2] };
        }
    });
    return map;
}

function loadDbRows() {
    const products = parseTsvBlock(queryDb(
        "USE db_belajaryuk; SELECT id, nama_produk, subkategori, gambar FROM products"
    ));
    const bootcamps = parseTsvBlock(queryDb(
        "USE db_belajaryuk; SELECT id, judul, kategori, gambar FROM bootcamp"
    ));
    return { products: products, bootcamps: bootcamps };
}

function legacySource(file, type) {
    const candidates = type === "elearning"
        ? [
            path.join(ROOT, "assets", "course", file),
            path.join(ROOT, "course", "assets", "images", "elearning", file)
        ]
        : [
            path.join(ROOT, "course", "assets", "images", "bootcamp", file),
            path.join(ROOT, "assets", "course", "bootcamp", file)
        ];

    for (const candidate of candidates) {
        if (fs.existsSync(candidate)) {
            const head = fs.readFileSync(candidate, "utf8").slice(0, 200);
            if (head.indexOf("<svg") !== -1 || head.indexOf("<?xml") !== -1) {
                return candidate;
            }
        }
    }
    return null;
}

function fallbackFor(file) {
    const base = path.basename(file, ".png");
    return { title: base.replace(/_/g, " "), subtitle: "", color: null };
}

function collectItems() {
    const items = [];
    const meta = loadMeta();
    const db = loadDbRows();

    function resolve(dirName, map, type) {
        fs.readdirSync(path.join(ROOT, "assets", dirName))
            .filter(function (f) { return f.endsWith(".png"); })
            .sort()
            .forEach(function (file) {
                const dbRow = map[file];
                const saved = meta[file];
                const legacy = legacySource(file, type);
                const old = legacy ? parseOldSvg(legacy) : fallbackFor(path.join(dirName, file));

                const title = dbRow
                    ? dbRow.title
                    : (saved && saved.title) || old.title;

                const themeKey = pickTheme(file + " " + title);

                const badge = dbRow
                    ? dbRow.badge
                    : (type === "elearning"
                        ? categoryLabel(themeKey)
                        : bootcampLabel(themeKey));

                const subtitle = dbRow
                    ? (type === "elearning" ? "Kelas E-Learning" : "Program Bootcamp Profesional")
                    : (saved && saved.subtitle) ||
                      (type === "elearning" ? (old.subtitle || "Kelas E-Learning") : (old.subtitle || "Program Bootcamp Profesional"));

                items.push({
                    file: path.join(ROOT, "assets", dirName, file),
                    key: file,
                    type: type,
                    title: title,
                    badge: badge,
                    subtitle: subtitle,
                    corner: type === "elearning" ? "E-Learning" : "Bootcamp",
                    theme: themeKey
                });
            });
    }

    resolve("elearning", db.products, "elearning");
    resolve("bootcamp", db.bootcamps, "bootcamp");

    const out = {};
    items.forEach(function (item) {
        out[item.key] = {
            title: item.title,
            badge: item.badge,
            subtitle: item.subtitle,
            corner: item.corner,
            theme: item.theme,
            type: item.type
        };
    });
    saveMeta(out);

    return items;
}

function findChrome() {
    for (const p of CHROME_CANDIDATES) {
        if (fs.existsSync(p)) return p;
    }
    throw new Error("Chrome/Edge tidak ditemukan");
}

const sleep = (ms) => new Promise(function (r) { setTimeout(r, ms); });

async function main() {
    const items = collectItems();
    const chrome = findChrome();
    console.log("Chrome: " + chrome);
    console.log("Gambar: " + items.length);

    const proc = spawn(chrome, [
        "--headless=new",
        "--remote-debugging-port=" + PORT,
        "--user-data-dir=" + PROFILE,
        "--no-first-run",
        "--no-default-browser-check",
        "--disable-gpu",
        "--hide-scrollbars",
        "about:blank"
    ], { stdio: "ignore" });

    let list = null;
    for (let i = 0; i < 60; i++) {
        try {
            list = await (await fetch("http://127.0.0.1:" + PORT + "/json/list")).json();
            if (list.some(function (t) { return t.type === "page"; })) break;
        } catch (e) { /* retry */ }
        await sleep(250);
    }
    if (!list) throw new Error("Chrome debugging endpoint tidak siap");

    const target = list.find(function (t) { return t.type === "page"; });
    const ws = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise(function (resolve, reject) {
        ws.addEventListener("open", resolve);
        ws.addEventListener("error", reject);
    });

    let id = 0;
    const pending = new Map();
    ws.addEventListener("message", function (ev) {
        const msg = JSON.parse(ev.data);
        if (msg.id && pending.has(msg.id)) {
            pending.get(msg.id)(msg);
            pending.delete(msg.id);
        }
    });
    const send = function (method, params) {
        return new Promise(function (resolve) {
            const i = ++id;
            pending.set(i, resolve);
            ws.send(JSON.stringify({ id: i, method: method, params: params || {} }));
        });
    };

    await send("Page.enable");
    await send("Emulation.setDeviceMetricsOverride", {
        width: 800, height: 450, deviceScaleFactor: 1, mobile: false
    });
    await send("Page.navigate", { url: "about:blank" });
    await sleep(300);

    let done = 0;
    for (const item of items) {
        const svg = buildSvg(item);
        const html = '<!doctype html><html><head><meta charset="utf-8"><style>html,body{margin:0;padding:0;background:#fff;width:800px;height:450px;overflow:hidden}</style></head><body>' + svg + "</body></html>";

        await send("Runtime.evaluate", {
            expression: "document.open();document.write(" + JSON.stringify(html) + ");document.close();",
            awaitPromise: false
        });
        await sleep(50);

        const shot = await send("Page.captureScreenshot", {
            format: "png",
            clip: { x: 0, y: 0, width: 800, height: 450, scale: 1 },
            captureBeyondViewport: true
        });

        if (!shot.result || !shot.result.data) {
            console.log("FAIL screenshot: " + item.file);
            continue;
        }

        fs.writeFileSync(item.file, Buffer.from(shot.result.data, "base64"));
        done++;
        if (done % 10 === 0) console.log("  " + done + "/" + items.length);
    }

    console.log("Selesai: " + done + "/" + items.length);
    proc.kill();
    process.exit(done === items.length ? 0 : 1);
}

main().catch(function (e) {
    console.error("ERR", e);
    process.exit(1);
});
