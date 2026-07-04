<div id="loader-overlay" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(10, 25, 47, 0.92); backdrop-filter:blur(8px); display:none; justify-content:center; align-items:center; z-index:9999999; flex-direction:column; gap:20px;">
    <div style="display:flex; align-items:center; gap:6px;">
        <div class="v-bar" style="width:5px; height:14px; background:#B7915F; border-radius:3px; animation:rrs-vibe 0.9s ease-in-out infinite;"></div>
        <div class="v-bar" style="width:5px; height:14px; background:#D4AF37; border-radius:3px; animation:rrs-vibe 0.9s ease-in-out infinite 0.1s;"></div>
        <div class="v-bar" style="width:5px; height:14px; background:#B7915F; border-radius:3px; animation:rrs-vibe 0.9s ease-in-out infinite 0.2s;"></div>
        <div class="v-bar" style="width:5px; height:14px; background:#D4AF37; border-radius:3px; animation:rrs-vibe 0.9s ease-in-out infinite 0.3s;"></div>
        <div class="v-bar" style="width:5px; height:14px; background:#B7915F; border-radius:3px; animation:rrs-vibe 0.9s ease-in-out infinite 0.4s;"></div>
        <div class="v-bar" style="width:5px; height:14px; background:#D4AF37; border-radius:3px; animation:rrs-vibe 0.9s ease-in-out infinite 0.5s;"></div>
    </div>
    <p style="font-family: 'Playfair Display', serif; color: #B7915F; font-size: 11px; letter-spacing: 0.3em; text-transform: uppercase; font-weight: 700;">RajaRam &amp; Sons</p>
</div>

<style>
    @keyframes rrs-vibe {
        0%, 100% { transform: scaleY(1); opacity: 0.6; }
        50% { transform: scaleY(2.6); opacity: 1; }
    }
</style>

<script>
    // 1. Click hote hi dikhao
    document.addEventListener("click", function(e) {
        let link = e.target.closest('a');
        if (link && link.href && !link.target && !link.href.startsWith('#')) {
            document.getElementById('loader-overlay').style.display = 'flex';
        }
    });

    // 2. Load hote hi hatao
    window.addEventListener("load", function() {
        document.getElementById('loader-overlay').style.display = 'none';
    });
</script>
