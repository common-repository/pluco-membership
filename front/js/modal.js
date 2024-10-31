// Get the modal
let modal = '';

// Get the button that opens the modal
const btns = Array.prototype.slice.call(document.getElementsByClassName("plco-open-modal"));

// Get the <span> element that closes the modal
const spans = Array.prototype.slice.call(document.getElementsByClassName("close"));

btns.forEach((btn) => {
    btn.onclick = function () {
        modal = document.getElementById(btn.dataset.id);

        if (modal) {
            modal.style.display = "block";
        }
    }
});

// When the user clicks on <span> (x), close the modal
spans.forEach((span) => {
    span.onclick = function () {
        modal.style.display = "none";
    }
});


// When the user clicks anywhere outside of the modal, close it
window.onclick = function (event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
}
