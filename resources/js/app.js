import '../css/app.css';

/* bootstrap */
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';


let page = 1;
const btn = document.getElementById("loadMoreBtn");

btn.addEventListener("click", function() {
    page++;
    const url = `${btn.dataset.url}?page=${page}`;

    fetch(url)
        .then(res => {
            if (!res.ok) throw new Error("Erro 404: rota não encontrada");
            return res.text();
        })
        .then(html => {
            if (html.trim() === "") {
                btn.style.display = "none";

                // Mostra a mensagem apenas uma vez
                if (!document.getElementById("noMoreEventsMsg")) {
                    const msg = document.createElement("p");
                    msg.id = "noMoreEventsMsg";
                    msg.className = "text-center mt-4 text-gray-600 font-semibold";
                    msg.innerText = "Não há mais eventos disponíveis.";
                    btn.parentNode.appendChild(msg);
                }
                return;
            }

            document.getElementById("events-container-grid")
                .insertAdjacentHTML('beforeend', html);
        })
        .catch(err => console.error("Erro ao carregar mais eventos:", err));
});



