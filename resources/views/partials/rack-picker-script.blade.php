<script>
    // Pemilih lokasi rak 3D — dipakai bareng Put Away & Picking. $locationCodes
    // (dari controller: emptyLocationCodes() atau occupiedLocationCodes()) jadi
    // daftar slot yang boleh diklik (hijau); sisanya abu-abu, tidak bisa diklik.
    (function () {
        const overlay = document.getElementById('rackPickerOverlay');
        if (!overlay || typeof THREE === 'undefined') return;

        const tabsEl = document.getElementById('rackPickerTabs');
        const closeBtn = document.getElementById('rackPickerClose');
        const RACKS = ['R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'R7', 'R8'];
        const COLUMNS = 9, ROWS = 5;
        const PICKABLE = new Set(@json($locationCodes));

        let scene, camera, renderer, container, meshes = [], activeInput = null;
        // dist default dipepetin (dari COLUMNS*1.5 ke COLUMNS*1.05) — rak yang
        // sama sekarang memenuhi frame lebih banyak, jadi tiap label dapat lebih
        // banyak piksel layar tanpa perlu user manual scroll-zoom dulu.
        let currentRack = 'R1', rotY = 0.22, rotX = -0.14, dist = COLUMNS * 1.05;

        function makeLabelTexture(code, pickable) {
            // Resolusi dibesarkan lagi (640x256/93px, dari 400x160/58px) —
            // sebelumnya masih buram terutama karena label dilihat dari sudut
            // miring (kamera isometrik), bukan tegak lurus, jadi butuh detail
            // sumber lebih tinggi. Anisotropic filtering (di bawah) yang benerin
            // blur akibat sudut pandang miring itu sendiri.
            const c = document.createElement('canvas');
            c.width = 640; c.height = 256;
            const ctx = c.getContext('2d');
            ctx.fillStyle = pickable ? 'rgba(240,253,244,0.95)' : 'rgba(241,245,249,0.9)';
            ctx.fillRect(16, 53, 608, 150);
            ctx.strokeStyle = pickable ? 'rgba(22,163,74,0.9)' : 'rgba(148,163,184,0.8)';
            ctx.lineWidth = 8;
            ctx.strokeRect(16, 53, 608, 150);
            ctx.fillStyle = pickable ? '#166534' : '#64748B';
            ctx.font = 'bold 93px Inter, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(code, 320, 131);
            const tex = new THREE.CanvasTexture(c);
            if (renderer) {
                tex.anisotropy = renderer.capabilities.getMaxAnisotropy();
            }
            return tex;
        }

        function ensureScene() {
            if (scene) return;
            container = document.getElementById('rackPicker3d');
            scene = new THREE.Scene();
            scene.background = new THREE.Color(0xE8EEF5);

            const W = container.clientWidth, H = container.clientHeight;
            camera = new THREE.PerspectiveCamera(40, W / H, 0.1, 1000);

            // pixelRatio cap dinaikkan dari 1.5 ke 2 (beda dari Rack Monitoring
            // yang sengaja dibatasi 1.5 buat performa) — modal ini cuma render
            // 1 rack (45 box) sekaligus jadi lebih ringan, worth it buat
            // ketajaman ekstra di layar high-DPI.
            renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(W, H);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
            container.appendChild(renderer.domElement);

            scene.add(new THREE.AmbientLight(0xffffff, 0.85));
            const dl = new THREE.DirectionalLight(0xffffff, 0.5);
            dl.position.set(6, 14, 16);
            scene.add(dl);

            function applyCam() {
                const cx = dist * Math.sin(rotY) * Math.cos(rotX);
                const cz = dist * Math.cos(rotY) * Math.cos(rotX);
                const cy = ROWS / 2 + dist * Math.sin(-rotX);
                camera.position.set(cx, cy, cz);
                camera.lookAt(0, ROWS / 2, 0);
            }

            let isDragging = false, moved = false, lastX = 0, lastY = 0;
            const raycaster = new THREE.Raycaster();
            const pointer = new THREE.Vector2();

            function handleClick(e) {
                const rect = renderer.domElement.getBoundingClientRect();
                pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
                pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
                raycaster.setFromCamera(pointer, camera);
                const hits = raycaster.intersectObjects(meshes);
                if (hits.length && hits[0].object.userData.pickable) {
                    selectCode(hits[0].object.userData.code);
                }
            }

            renderer.domElement.addEventListener('pointerdown', e => { isDragging = true; moved = false; lastX = e.clientX; lastY = e.clientY; });
            window.addEventListener('pointerup', e => {
                if (overlay.style.display === 'none') return;
                if (isDragging && !moved) handleClick(e);
                isDragging = false;
            });
            window.addEventListener('pointermove', e => {
                if (!isDragging || overlay.style.display === 'none') return;
                if (Math.abs(e.clientX - lastX) + Math.abs(e.clientY - lastY) > 3) moved = true;
                rotY += (e.clientX - lastX) * 0.006;
                rotX = Math.max(-0.5, Math.min(0.35, rotX + (e.clientY - lastY) * 0.005));
                lastX = e.clientX; lastY = e.clientY;
            });
            renderer.domElement.addEventListener('wheel', e => {
                e.preventDefault();
                dist = Math.max(COLUMNS * 0.7, Math.min(COLUMNS * 2.0, dist + e.deltaY * 0.02));
            }, { passive: false });

            function animate() {
                requestAnimationFrame(animate);
                if (overlay.style.display === 'none') return;
                applyCam();
                renderer.render(scene, camera);
            }
            applyCam();
            animate();

            window.addEventListener('resize', () => {
                if (!container || overlay.style.display === 'none') return;
                const w = container.clientWidth, h = container.clientHeight;
                if (!w || !h) return;
                camera.aspect = w / h; camera.updateProjectionMatrix();
                renderer.setSize(w, h);
            });
        }

        function renderRack(rackName) {
            meshes.forEach(m => scene.remove(m));
            meshes = [];
            const crateGeo = new THREE.BoxGeometry(0.88, 0.8, 0.95);
            const labelGeo = new THREE.PlaneGeometry(0.74, 0.30);
            for (let col = 1; col <= COLUMNS; col++) {
                for (let layer = 1; layer <= ROWS; layer++) {
                    const code = `${rackName}C${col}${layer}`;
                    const pickable = PICKABLE.has(code);
                    const mat = new THREE.MeshStandardMaterial({ color: pickable ? 0x22C55E : 0xCBD5E1 });
                    const mesh = new THREE.Mesh(crateGeo, mat);
                    const x = ((COLUMNS + 1) / 2 - col) * 1.0;
                    mesh.position.set(x, layer * 1.0, 0);
                    mesh.userData = { code: code, pickable: pickable };
                    const labelMat = new THREE.MeshBasicMaterial({ map: makeLabelTexture(code, pickable), transparent: true });
                    const label = new THREE.Mesh(labelGeo, labelMat);
                    label.position.set(0, 0, 0.481);
                    mesh.add(label);
                    scene.add(mesh);
                    meshes.push(mesh);
                }
            }
        }

        function selectCode(code) {
            if (activeInput) {
                activeInput.value = code;
                activeInput.dispatchEvent(new Event('input'));
            }
            closeModal();
        }

        function openModal(inputEl) {
            activeInput = inputEl;
            overlay.style.display = 'flex';
            ensureScene();
            renderRack(currentRack);
            requestAnimationFrame(() => {
                if (!container || !renderer) return;
                const w = container.clientWidth, h = container.clientHeight;
                camera.aspect = w / h; camera.updateProjectionMatrix();
                renderer.setSize(w, h);
            });
        }

        function closeModal() {
            overlay.style.display = 'none';
            activeInput = null;
        }

        tabsEl.innerHTML = RACKS.map(r => `<a href="#" class="rack-tab mono ${r === currentRack ? 'active' : ''}" data-rack="${r}">RACK ${r.replace('R', '')}</a>`).join('');
        tabsEl.addEventListener('click', function (e) {
            const a = e.target.closest('[data-rack]');
            if (!a) return;
            e.preventDefault();
            currentRack = a.dataset.rack;
            tabsEl.querySelectorAll('.rack-tab').forEach(t => t.classList.toggle('active', t.dataset.rack === currentRack));
            renderRack(currentRack);
        });
        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) closeModal(); });

        window.openRackPicker = openModal;
    })();
</script>
