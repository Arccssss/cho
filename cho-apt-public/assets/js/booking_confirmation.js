document.addEventListener('DOMContentLoaded', function() {
    // Check if the qrData was passed from PHP
    if (window.qrData && document.getElementById("qrcode")) {
        var qrcode = new QRCode(document.getElementById("qrcode"), {
            text: window.qrData,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    }
});

// Download QR Code function
function downloadQR() {
    var canvas = document.querySelector('#qrcode canvas');
    if (canvas && window.refNumber) {
        var link = document.createElement('a');
        link.download = 'CHO_Appointment_' + window.refNumber + '.png';
        link.href = canvas.toDataURL();
        link.click();
    }
}