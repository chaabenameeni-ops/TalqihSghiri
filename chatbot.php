<button id="TS_ChatToggle" style="position: fixed !important; right: 25px !important; bottom: 25px !important; width: 60px !important; height: 60px !important; background-color: #d87093 !important; color: white !important; border: none !important; border-radius: 50% !important; cursor: pointer !important; z-index: 999999 !important; font-size: 28px !important; box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important; display: flex !important; align-items: center !important; justify-content: center !important;">
  💬
</button>

<div id="TS_ChatWindow" style="position: fixed !important; right: 25px !important; bottom: 95px !important; width: 340px !important; height: 450px !important; background-color: white !important; border-radius: 15px !important; box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important; z-index: 999999 !important; display: none; flex-direction: column !important; overflow: hidden !important; border: 1px solid #eee !important; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;">
    
    <div style="background-color: #d87093 !important; color: white !important; padding: 15px !important; display: flex !important; justify-content: space-between !important; align-items: center !important;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <div style="width: 10px; height: 10px; background: #2ecc71; border-radius: 50%;"></div>
            <span style="font-weight: bold; font-size: 14px;">TalqihSghiri Assistant</span>
        </div>
        <span id="TS_ChatClose" style="cursor: pointer !important; font-size: 20px !important; font-weight: bold !important;">✕</span>
    </div>

    <div id="TS_ChatBox" style="flex: 1 !important; padding: 15px !important; overflow-y: auto !important; background-color: #f9f9f9 !important; display: flex !important; flex-direction: column !important; gap: 10px !important;">
        <div style="background-color: #e9e9e9; color: #333; padding: 10px 14px; border-radius: 12px; align-self: flex-start; max-width: 80%; font-size: 13px; line-height: 1.4;">
    Ravi de vous voir aujourd'hui 🩷
        </div>
    </div>

    <div style="padding: 12px !important; border-top: 1px solid #eee !important; display: flex !important; gap: 8px !important; background: white !important;">
        <input type="text" id="TS_UserInput" placeholder="Écrivez ici..." style="flex: 1 !important; border: 1px solid #ddd !important; border-radius: 20px !important; padding: 10px 15px !important; outline: none !important; font-size: 13px !important; background: white !important; color: black !important;">
        <button id="TS_SendBtn" style="background-color: #d87093 !important; color: white !important; border: none !important; border-radius: 50% !important; width: 38px !important; height: 38px !important; cursor: pointer !important; display: flex !important; align-items: center !important; justify-content: center !important;">➤</button>
    </div>
</div>

<script>
(function() {
    const toggle = document.getElementById('TS_ChatToggle');
    const win = document.getElementById('TS_ChatWindow');
    const close = document.getElementById('TS_ChatClose');
    const box = document.getElementById('TS_ChatBox');
    const input = document.getElementById('TS_UserInput');
    const btn = document.getElementById('TS_SendBtn');

    toggle.onclick = () => { win.style.display = (win.style.display === 'none' || win.style.display === '') ? 'flex' : 'none'; };
    close.onclick = () => { win.style.display = 'none'; };

    function addMessage(text, isUser) {
        const msgDiv = document.createElement('div');
        msgDiv.innerText = text;
        msgDiv.style = `padding: 10px 14px; border-radius: 12px; font-size: 13px; max-width: 80%; line-height: 1.4; margin-bottom: 5px; ${isUser ? 'background:#fce4ec; color:#d87093; align-self:flex-end; font-weight:bold;' : 'background:#e9e9e9; color:#333; align-self:flex-start;'}`;
        box.appendChild(msgDiv);
        box.scrollTop = box.scrollHeight;
    }

    async function handleChat() {
        const message = input.value.trim();
        if (!message) return;

        addMessage(message, true);
        input.value = "";

        try {
            const response = await fetch('chat-proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });

            // تحويل الرد إلى نص أولاً للتأكد من عدم وجود أخطاء PHP مخفية
            const textData = await response.text();
            try {
                const data = JSON.parse(textData);
                addMessage(data.reply || "Désolé, أنا لم أفهم ذلك.", false);
            } catch (e) {
                console.error("JSON Parse Error:", textData);
                addMessage("Erreur de formatage du serveur.", false);
            }
        } catch (error) {
            addMessage("Erreur: Impossible de joindre le serveur.", false);
        }
    }

    btn.onclick = handleChat;
    input.onkeypress = (e) => { if (e.key === 'Enter') handleChat(); };
})();
</script>