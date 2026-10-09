/* FlashSite Core 3.1.0 · Efeitos de fundo e de topo ligados por classe (o MCP só aplica a classe).
   .fs-moleculas   fundo animado (WebGL) com células, membranas e partículas, atrás do conteúdo do bloco.
                   Cores pelas variáveis CSS do bloco: --fs-mol-fundo-1, --fs-mol-fundo-2, --fs-mol-cor-1,
                   --fs-mol-cor-2 (hex). Para quando sai do ecrã; com "reduzir movimento" fica parado.
   .fs-topo-fixo   ganha a classe is-rolado depois de 40 px de rolagem (fundo sólido no topo transparente). */
(function () {
    'use strict';

    function hex(v, d) {
        v = String(v || '').trim().replace('#', '');
        if (v.length === 3) { v = v[0] + v[0] + v[1] + v[1] + v[2] + v[2]; }
        if (!/^[0-9a-f]{6}$/i.test(v)) { return d; }
        return [parseInt(v.slice(0, 2), 16) / 255, parseInt(v.slice(2, 4), 16) / 255, parseInt(v.slice(4, 6), 16) / 255];
    }

    var VS = 'attribute vec2 a;void main(){gl_Position=vec4(a,0.0,1.0);}';
    var FS = [
        'precision mediump float;',
        'uniform float t0;uniform vec2 r;uniform vec3 f1;uniform vec3 f2;uniform vec3 c1;uniform vec3 c2;',
        'float h(vec2 p){return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453123);}',
        'float n(vec2 p){vec2 i=floor(p);vec2 f=fract(p);vec2 u=f*f*(3.0-2.0*f);',
        'return mix(mix(h(i),h(i+vec2(1.0,0.0)),u.x),mix(h(i+vec2(0.0,1.0)),h(i+vec2(1.0,1.0)),u.x),u.y);}',
        'void main(){',
        'vec2 uv=(gl_FragCoord.xy-0.5*r)/min(r.x,r.y);float t=t0*0.35;',
        'vec3 col=mix(f1,f2,uv.y+0.5);',
        'vec2 p=uv*2.2;float m=n(p+vec2(sin(t*0.4),cos(t*0.3)));m+=0.5*n(p*1.8-vec2(cos(t*0.3),sin(t*0.4)));',
        'float mem=smoothstep(0.42,0.46,m)*smoothstep(0.58,0.54,m);col+=mix(f2,c1,0.35)*mem*0.9;',
        'vec2 q=uv*3.8+vec2(sin(t*0.25)*0.2,cos(t*0.2)*0.2);vec2 gv=fract(q)-0.5;float hh=h(floor(q));',
        'vec2 o=vec2(sin(t*1.1+hh*6.28),cos(t+hh*6.28))*0.2;float d=length(gv-o);',
        'vec3 cc=mix(c1,c2,hh);col+=cc*smoothstep(0.08,0.02,d)*1.2;col+=mix(f2,c1,0.4)*smoothstep(0.22,0.03,d)*0.3;',
        'for(int i=0;i<6;i++){float fi=float(i);vec2 pp=vec2(sin(t*0.5+fi*1.8),cos(t*0.4+fi*2.2)*0.6);',
        'float pd=length(uv-pp);col+=mix(c1,c2,sin(fi+t)*0.5+0.5)*(0.012/(pd*pd+0.008))*0.1;}',
        'col*=1.0-smoothstep(0.5,1.4,length(uv));',
        'gl_FragColor=vec4(col,1.0);}'
    ].join('\n');

    function moleculas(el) {
        if (el.getAttribute('data-fs-moleculas')) { return; }
        el.setAttribute('data-fs-moleculas', '1');
        var canvas = document.createElement('canvas');
        canvas.className = 'fs-moleculas__canvas';
        canvas.setAttribute('aria-hidden', 'true');
        el.insertBefore(canvas, el.firstChild);
        var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!gl) { canvas.remove(); return; }

        function sh(type, src) { var s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; }
        var prog = gl.createProgram();
        gl.attachShader(prog, sh(gl.VERTEX_SHADER, VS));
        gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, FS));
        gl.linkProgram(prog);
        if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) { canvas.remove(); return; }
        gl.useProgram(prog);
        var buf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
        var a = gl.getAttribLocation(prog, 'a');
        gl.enableVertexAttribArray(a);
        gl.vertexAttribPointer(a, 2, gl.FLOAT, false, 0, 0);

        var cs = getComputedStyle(el);
        var U = function (k) { return gl.getUniformLocation(prog, k); };
        gl.uniform3fv(U('f1'), hex(cs.getPropertyValue('--fs-mol-fundo-1'), [0, 0.13, 0.16]));
        gl.uniform3fv(U('f2'), hex(cs.getPropertyValue('--fs-mol-fundo-2'), [0, 0.27, 0.33]));
        gl.uniform3fv(U('c1'), hex(cs.getPropertyValue('--fs-mol-cor-1'), [0.18, 0.85, 0.72]));
        gl.uniform3fv(U('c2'), hex(cs.getPropertyValue('--fs-mol-cor-2'), [0.75, 0.95, 0.39]));
        var uT = U('t0'), uR = U('r');

        var scale = Math.min(window.devicePixelRatio || 1, 1.5);
        function size() {
            var w = Math.max(1, Math.round(el.clientWidth * scale)), hgt = Math.max(1, Math.round(el.clientHeight * scale));
            if (canvas.width !== w || canvas.height !== hgt) { canvas.width = w; canvas.height = hgt; }
            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.uniform2f(uR, canvas.width, canvas.height);
        }
        var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var visible = true, raf = 0, start = performance.now();
        function frame(now) {
            raf = 0;
            size();
            gl.uniform1f(uT, (now - start) / 1000 + 8);
            gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
            if (!still && visible) { raf = requestAnimationFrame(frame); }
        }
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                visible = entries[0].isIntersecting;
                if (visible && !raf) { raf = requestAnimationFrame(frame); }
            }).observe(el);
        }
        if ('ResizeObserver' in window) { new ResizeObserver(function () { if (still) { frame(performance.now()); } }).observe(el); }
        el.classList.add('fs-moleculas--ready');
        raf = requestAnimationFrame(frame);
    }

    function topo() {
        var els = document.querySelectorAll('.fs-topo-fixo');
        if (!els.length) { return; }
        var on = function () {
            var r = window.scrollY > 40;
            Array.prototype.forEach.call(els, function (e) { e.classList.toggle('is-rolado', r); });
        };
        window.addEventListener('scroll', on, { passive: true });
        on();
    }

    function init() {
        // Depois do carregamento: o fundo não atrasa o primeiro desenho da página.
        var run = function () { Array.prototype.forEach.call(document.querySelectorAll('.fs-moleculas'), moleculas); };
        if ('requestIdleCallback' in window) { requestIdleCallback(run, { timeout: 1200 }); } else { setTimeout(run, 300); }
        topo();
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
