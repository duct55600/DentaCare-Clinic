// Callback function được gọi tự động khi Google Maps API load xong
// Đảm bảo function được định nghĩa global để Google Maps API có thể gọi
// Sử dụng Hà Nội, Việt Nam làm tọa độ mặc định
window.initGoogleMap = function() {
    try {
        // Kiểm tra xem Google Maps API đã load chưa
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            // Không log warning để tránh spam console
            // Retry sau 100ms, tối đa 10 lần
            if (!window._googleMapRetryCount) {
                window._googleMapRetryCount = 0;
            }
            if (window._googleMapRetryCount < 10) {
                window._googleMapRetryCount++;
                setTimeout(window.initGoogleMap, 100);
            }
            return;
        }
        
        // Reset retry counter
        window._googleMapRetryCount = 0;
        
        // Kiểm tra xem có element #map không
        var mapElement = document.getElementById('map');
        if (!mapElement) {
            // Không có map element, không cần khởi tạo - không phải lỗi
            return;
        }

        // Tọa độ Hà Nội, Việt Nam
        var hanoiLatlng = new google.maps.LatLng(21.0285, 105.8542);
        
        var mapOptions = {
            zoom: 13,
            center: hanoiLatlng,
            scrollwheel: false,
            styles: [
                {
                    "featureType": "administrative.country",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "visibility": "simplified"
                        }
                    ]
                }
            ]
        };

        // Create the Google Map
        var map = new google.maps.Map(mapElement, mapOptions);
        
        // Thêm marker cho Hà Nội
        new google.maps.Marker({
            position: hanoiLatlng,
            map: map,
            title: 'DentaCare - Hà Nội'
        });
        
    } catch (error) {
        // Không log error để tránh làm gián đoạn các script khác
        // Chỉ log trong development mode
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.warn('Google Map initialization skipped:', error.message);
        }
    }
};