import Alpine from "alpinejs";
import Chart from "chart.js/auto";
window.Alpine = Alpine;
Alpine.start();
document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", (event) => {
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
            return;
        }
        if (form.dataset.busy) {
            event.preventDefault();
            return;
        }
        form.dataset.busy = "true";
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.dataset.original = button.textContent;
            button.textContent = "Đang xử lý…";
        }
    });
});
window.addEventListener("pageshow", () =>
    document.querySelectorAll("form[data-busy]").forEach((form) => {
        delete form.dataset.busy;
        const b = form.querySelector('button[type="submit"]');
        if (b?.dataset.original) {
            b.disabled = false;
            b.textContent = b.dataset.original;
        }
    }),
);
const revenue = document.getElementById("revenue-chart");
if (revenue) {
    const data = JSON.parse(revenue.dataset.values);
    new Chart(revenue, {
        type: "line",
        data: {
            labels: data.map((x) => x.day),
            datasets: [
                {
                    label: "Doanh thu đã thu (₫)",
                    data: data.map((x) => x.amount),
                    borderColor: "#191919",
                    backgroundColor: "#eeeeea",
                    fill: true,
                    tension: 0.2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
        },
    });
}
const states = document.getElementById("states-chart");
if (states) {
    const data = JSON.parse(states.dataset.values);
    new Chart(states, {
        type: "bar",
        data: {
            labels: data.map((x) => x.label),
            datasets: [
                {
                    label: "Đơn con",
                    data: data.map((x) => x.count),
                    backgroundColor: "#595b56",
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
        },
    });
}
