
function printPDF() {
    // Get the modal element and iframe element
    var modal = document.getElementById('pdfModal');
    var iframe = document.getElementById('pdfIframe');
    console.log(pdfUrl)
    // Use AJAX to get the PDF content using the `pdfUrl` defined in the Blade file
    var xhr = new XMLHttpRequest();
    xhr.open('GET', pdfUrl, true); // Use the global `pdfUrl` variable
    xhr.responseType = 'blob'; // Expect a binary PDF response

    xhr.onload = function () {
        if (xhr.status === 200) {
            var blob = xhr.response; // Get the binary PDF data
            var url = URL.createObjectURL(blob); // Create a URL for the PDF file
            iframe.src = url; // Set the iframe src to the PDF file URL

            // Display the modal
            modal.style.display = 'block';

            // Trigger the print dialog once the PDF is loaded in the iframe
            iframe.onload = function () {
                iframe.focus();
                iframe.contentWindow.print();
            };
        }
    };

    xhr.send(); // Send the request to fetch the PDF
}

// Close the modal when the user clicks outside of it or on the close button
window.onclick = function (event) {
    var modal = document.getElementById('pdfModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
};

function closeModal() {
    var modal = document.getElementById('pdfModal');
    modal.style.display = 'none';
}
