import "./bootstrap";

document.addEventListener("livewire:init", () => {
    Livewire.on("notify", (data) => {
        const container = document.getElementById("toast-container");
        if (!container) return;

        const toast = document.createElement("div");
        const isSuccess =
            data[0]?.type === "success" || data.type === "success";
        const message = data[0]?.message || data.message || "";
        const bgClass = isSuccess ? "bg-emerald-600" : "bg-red-600";

        toast.className = `${bgClass} text-white px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center space-x-2 transform transition-all duration-300 translate-x-full`;
        toast.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-2 text-white/80 hover:text-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove("translate-x-full");
            toast.classList.add("translate-x-0");
        });

        setTimeout(() => {
            toast.classList.add("translate-x-full");
            toast.classList.remove("translate-x-0");
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    });
});
