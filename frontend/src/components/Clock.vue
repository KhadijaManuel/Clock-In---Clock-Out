<template>
  <div class="attendance-dashboard">
    <main class="main-content">
      <div class="cards-grid">
        <!-- Left Card - Timer -->
        <div class="card timer-card">
          <h2>Hours Worked</h2>
          <div class="timer-container">
            <div class="svg-container">
              <svg class="progress-ring" viewBox="0 0 280 280">
                <!-- Gray background circle -->
                <circle
                  class="progress-ring-background"
                  stroke="#E0E0E0"
                  stroke-width="10"
                  fill="transparent"
                  r="125"
                  cx="140"
                  cy="140"
                />
                <!-- Green progress circle -->
                <circle
                  class="progress-ring-circle"
                  stroke="#27AE60"
                  stroke-width="10"
                  fill="transparent"
                  r="125"
                  cx="140"
                  cy="140"
                  :style="circleStyle"
                />
              </svg>
              <div class="timer-content">
                <div class="timer-display">
                  {{ formattedTime }}
                </div>
                <button 
                  class="clock-btn" 
                  :class="{ 'clock-out': isClockedIn }"
                  @click="toggleClock"
                  :disabled="locationLoading"
                >
                  {{ locationLoading ? 'Getting Location...' : (isClockedIn ? 'Clock Out' : 'Clock In') }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column Cards -->
        <div class="right-column">
          <!-- Middle Card - Arrival & Location -->
          <div class="card info-card">
            <h2>Arrival Time & Location</h2>
            <div class="info-section">
              <div class="info-item">
                <label>Arrival Time:</label>
                <span>{{ arrivalTime || '--:--:--' }}</span>
              </div>
              <div class="info-item">
                <label>Location:</label>
                <span class="location">{{ currentLocation || 'Not yet clocked in' }}</span>
              </div>
              
              <div v-if="locationError" class="error-message">
                ❌ {{ locationError }}
              </div>
              
              <div v-if="coordinates" class="coordinates">
                📍 Coordinates: {{ coordinates.lat.toFixed(6) }}, {{ coordinates.lng.toFixed(6) }}
              </div>
              
              <div v-if="locationStatus" class="status-message" :class="locationStatus.type">
                {{ locationStatus.message }}
              </div>
            </div>
          </div>

          <!-- Bottom Card - Today's Activity -->
          <div class="card activity-card">
            <h2>Today's Activity</h2>
            <div class="table-container">
              <table class="activity-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(activity, index) in activities" :key="index">
                    <td>{{ activity.date }}</td>
                    <td>{{ activity.clockIn }}</td>
                    <td>{{ activity.clockOut || '--:--' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// Timer state
const isClockedIn = ref(false)
const secondsWorked = ref(0)
let timerInterval = null

// Location and time data
const arrivalTime = ref('')
const currentLocation = ref('')
const locationError = ref('')
const coordinates = ref(null)
const locationLoading = ref(false)
const activities = ref([])

// Location status for better user feedback
const locationStatus = ref(null)

// Format time for display
const formattedTime = computed(() => {
  const hours = Math.floor(secondsWorked.value / 3600)
  const minutes = Math.floor((secondsWorked.value % 3600) / 60)
  const seconds = secondsWorked.value % 60
  
  return `${hours.toString().padStart(2, '0')}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`
})

// Circle animation style
const circleStyle = computed(() => {
  const circumference = 2 * Math.PI * 125
  const totalSeconds = 8 * 3600 // 8 hours in seconds
  const progress = Math.min((secondsWorked.value / totalSeconds) * circumference, circumference)
  const offset = circumference - progress
  
  return {
    strokeDasharray: `${circumference} ${circumference}`,
    strokeDashoffset: offset
  }
})

// NEW: Better geocoding with multiple services
const getAddressFromCoordinates = async (latitude, longitude) => {
  console.log('🔄 Starting geocoding for:', latitude, longitude);
  
  const services = [
    // Service 1: OpenStreetMap with better parsing
    {
      name: 'OpenStreetMap',
      url: `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
      parser: (data) => {
        console.log('OSM Response:', data);
        if (!data.address) return null;
        
        const addr = data.address;
        
        // Try to build a proper address
        let addressParts = [];
        
        // Street level
        if (addr.house_number && addr.road) {
          addressParts.push(`${addr.house_number} ${addr.road}`);
        } else if (addr.road) {
          addressParts.push(addr.road);
        }
        
        // Area level
        if (addr.neighbourhood) addressParts.push(addr.neighbourhood);
        if (addr.suburb && addr.suburb !== addr.neighbourhood) addressParts.push(addr.suburb);
        
        // City level
        if (addr.city) addressParts.push(addr.city);
        else if (addr.town) addressParts.push(addr.town);
        else if (addr.village) addressParts.push(addr.village);
        
        // Region level
        if (addr.state) addressParts.push(addr.state);
        if (addr.country) addressParts.push(addr.country);
        
        if (addressParts.length > 0) {
          return addressParts.join(', ');
        }
        
        // Fallback to display_name
        if (data.display_name) {
          return data.display_name.split(', ').slice(0, 4).join(', ');
        }
        
        return null;
      }
    },
    // Service 2: BigDataCloud (free, no API key)
    {
      name: 'BigDataCloud',
      url: `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`,
      parser: (data) => {
        console.log('BigDataCloud Response:', data);
        if (data.locality || data.city) {
          const parts = [
            data.localityInfo?.administrative[2]?.name, // City
            data.localityInfo?.administrative[1]?.name, // Region
            data.countryName
          ].filter(Boolean);
          return parts.join(', ') || data.locality || data.city;
        }
        return null;
      }
    },
    // Service 3: LocationIQ (free tier with demo key)
    {
      name: 'LocationIQ',
      url: `https://us1.locationiq.com/v1/reverse.php?key=pk.d0b8b34b917148fa48b4f0b1c7106c80&lat=${latitude}&lon=${longitude}&format=json`,
      parser: (data) => {
        console.log('LocationIQ Response:', data);
        return data.display_name || null;
      }
    }
  ];

  // Try each service until we get a good address
  for (const service of services) {
    try {
      console.log(`Trying ${service.name}...`);
      
      const response = await fetch(service.url, {
        headers: {
          'Accept': 'application/json',
          'User-Agent': 'AttendanceApp/1.0'
        }
      });
      
      if (!response.ok) {
        console.log(`${service.name} failed with status: ${response.status}`);
        continue;
      }
      
      const data = await response.json();
      const address = service.parser(data);
      
      if (address && address.length > 5) { // Basic validation
        console.log(`✅ Success with ${service.name}:`, address);
        return address;
      } else {
        console.log(`${service.name} returned invalid address:`, address);
      }
      
    } catch (error) {
      console.log(`${service.name} error:`, error);
      continue;
    }
  }

  // If all services fail, return coordinates in a nice format
  console.log('❌ All geocoding services failed, using coordinates');
  return `Near coordinates: ${latitude.toFixed(4)}°N, ${longitude.toFixed(4)}°E`;
}

// Check if geolocation is supported
const checkGeolocationSupport = () => {
  if (!navigator.geolocation) {
    locationError.value = 'Your browser does not support geolocation. Try using Chrome, Firefox, or Edge.'
    return false
  }
  return true
}

// Toggle clock in/out
const toggleClock = async () => {
  if (!isClockedIn.value) {
    // Clock In
    if (!checkGeolocationSupport()) return
    
    isClockedIn.value = true
    arrivalTime.value = new Date().toLocaleTimeString()
    locationError.value = ''
    coordinates.value = null
    locationLoading.value = true
    
    try {
      locationStatus.value = { type: 'info', message: '🔄 Getting your precise location...' }
      await fetchLocation()
    } catch (error) {
      console.error('Location error:', error)
      locationError.value = error.message || 'Failed to get location'
    } finally {
      locationLoading.value = false
    }
    
    // Start timer
    timerInterval = setInterval(() => {
      secondsWorked.value++
    }, 1000)
    
    // Add to activities
    activities.value.unshift({
      date: new Date().toLocaleDateString(),
      clockIn: arrivalTime.value,
      clockOut: ''
    })
  } else {
    // Clock Out
    isClockedIn.value = false
    clearInterval(timerInterval)
    
    // Update activities with clock out time
    if (activities.value.length > 0) {
      activities.value[0].clockOut = new Date().toLocaleTimeString()
    }
    
    // Reset timer
    secondsWorked.value = 0
  }
}

// Fetch current location
const fetchLocation = () => {
  return new Promise((resolve, reject) => {
    locationStatus.value = { type: 'info', message: 'Requesting location access...' }

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          const { latitude, longitude, accuracy } = position.coords
          console.log('Raw coordinates:', { latitude, longitude, accuracy: accuracy + ' meters' })
          
          // Store coordinates
          coordinates.value = { lat: latitude, lng: longitude }
          
          // Get address from coordinates
          locationStatus.value = { type: 'info', message: 'Finding your address...' }
          const address = await getAddressFromCoordinates(latitude, longitude)
          currentLocation.value = address
          locationStatus.value = { type: 'success', message: 'Location found!' }
          
          console.log('Final address:', address)
          
          // Clear status after 3 seconds
          setTimeout(() => {
            locationStatus.value = null
          }, 3000)
          
          resolve()
        } catch (error) {
          console.error('Error getting address:', error)
          locationStatus.value = { type: 'error', message: '⚠️ Got coordinates but failed to get address' }
          currentLocation.value = `Near coordinates: ${coordinates.value.lat.toFixed(4)}°N, ${coordinates.value.lng.toFixed(4)}°E`
          resolve() // Still resolve since we have coordinates
        }
      },
      (error) => {
        let errorMessage = 'Location access denied'
        
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage = '📍 Location permission denied. Please allow location access and refresh the page.'
            break
          case error.POSITION_UNAVAILABLE:
            errorMessage = '📍 Location unavailable. Make sure your device location is turned ON.'
            break
          case error.TIMEOUT:
            errorMessage = '📍 Location request timed out. Please try again.'
            break
          default:
            errorMessage = '📍 An unknown error occurred while getting location.'
            break
        }
        
        locationError.value = errorMessage
        locationStatus.value = { type: 'error', message: errorMessage }
        reject(error)
      },
      {
        enableHighAccuracy: true, // Get the most accurate location
        timeout: 15000, // 15 second timeout
        maximumAge: 0 // Don't use cached location
      }
    )
  })
}

// Test location manually with detailed logging
const testLocation = async () => {
  console.log('🧪 Starting location test...')
  if (checkGeolocationSupport()) {
    locationStatus.value = { type: 'info', message: '🧪 Testing location services...' }
    try {
      await fetchLocation()
      console.log('✅ Location test completed successfully!')
    } catch (error) {
      console.error('❌ Location test failed:', error)
    }
  }
}

// Make test function available globally for debugging
if (typeof window !== 'undefined') {
  window.testLocation = testLocation
  window.getCurrentCoords = () => coordinates.value
}

// Cleanup on unmount
onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})

// Initial check
onMounted(() => {
  checkGeolocationSupport()
  console.log('📍 Location services initialized. Use testLocation() in console to debug.')
})
</script>

<style scoped>
.attendance-dashboard {
  min-height: 100vh;
  background-color: #E8F9F2;
  font-family: 'Poppins', sans-serif;
  padding: 1rem;
}

.main-content {
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}

.cards-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  align-items: start;
}

.right-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0,0,0,0.05);
  width: 100%;
  box-sizing: border-box;
}

.card h2 {
  margin: 0 0 1rem 0;
  color: #2C3E50;
  font-size: 1.1rem;
  font-weight: 600;
}

.timer-card {
  grid-column: 1;
}

.timer-container {
  width: 100%;
  margin: 0 auto 1rem;
}

/* Responsive SVG Container */
.svg-container {
  position: relative;
  width: 100%;
  max-width: 280px;
  margin: 0 auto;
}

.progress-ring {
  width: 100%;
  height: auto;
  display: block;
}

.progress-ring-circle {
  transition: stroke-dashoffset 1s linear;
  transform: rotate(-90deg);
  transform-origin: 50% 50%;
}

.timer-content {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  width: 80%;
  max-width: 200px;
}

.timer-display {
  font-size: clamp(0.9rem, 3vw, 1.1rem);
  font-weight: 600;
  color: #2C3E50;
  margin-bottom: 1rem;
  word-break: break-word;
  line-height: 1.3;
}

.clock-btn {
  width: 100%;
  max-width: 140px;
  padding: clamp(8px, 2vw, 12px) clamp(16px, 3vw, 24px);
  background-color: #C0392B;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: clamp(0.8rem, 2.5vw, 0.9rem);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  margin: 0 auto;
  display: block;
}

.clock-btn.clock-out {
  background-color: #27AE60;
}

.clock-btn:disabled {
  background-color: #95a5a6;
  cursor: not-allowed;
  transform: none !important;
}

.clock-btn:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-2px);
}

.info-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.info-item label {
  font-weight: 600;
  color: #7F8C8D;
  font-size: clamp(0.75rem, 2vw, 0.8rem);
}

.info-item span {
  font-size: clamp(0.8rem, 2.5vw, 0.9rem);
  color: #2C3E50;
}

.location {
  word-break: break-word;
  font-size: clamp(0.8rem, 2.5vw, 0.9rem);
  line-height: 1.4;
  color: #2C3E50;
  font-weight: 500;
}

.error-message {
  color: #C0392B;
  font-size: clamp(0.75rem, 2vw, 0.8rem);
  margin-top: 0.5rem;
  background-color: #FDEDED;
  padding: 0.75rem;
  border-radius: 6px;
  border-left: 4px solid #C0392B;
}

.status-message {
  font-size: clamp(0.75rem, 2vw, 0.8rem);
  margin-top: 0.5rem;
  padding: 0.75rem;
  border-radius: 6px;
  font-weight: 500;
}

.status-message.info {
  background-color: #E8F4FD;
  color: #2980B9;
  border-left: 4px solid #2980B9;
}

.status-message.success {
  background-color: #E8F8F5;
  color: #27AE60;
  border-left: 4px solid #27AE60;
}

.status-message.error {
  background-color: #FDEDED;
  color: #C0392B;
  border-left: 4px solid #C0392B;
}

.coordinates {
  color: #7F8C8D;
  font-size: clamp(0.65rem, 1.8vw, 0.7rem);
  margin-top: 0.5rem;
  font-family: monospace;
  background-color: #F8F9FA;
  padding: 0.5rem;
  border-radius: 4px;
  border: 1px solid #ECF0F1;
  word-break: break-all;
}

.activity-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 0.5rem;
  font-size: clamp(0.7rem, 2vw, 0.8rem);
  min-height: 95px;
}

.activity-table th,
.activity-table td {
  padding: clamp(0.4rem, 1.5vw, 0.6rem);
  text-align: left;
  border-bottom: 1px solid #ECF0F1;
  word-break: break-word;
}

.activity-table th {
  background-color: #F8F9FA;
  font-weight: 600;
  color: #2C3E50;
}

.activity-table tbody tr:hover {
  background-color: #F8F9FA;
}

/* Tablet Styles */
@media (max-width: 1024px) {
  .attendance-dashboard {
    padding: 1rem;
  }
  
  .cards-grid {
    gap: 1.25rem;
  }
  
  .card {
    padding: 1.25rem;
  }
  
  .svg-container {
    max-width: 240px;
  }
}

/* Mobile Styles */
@media (max-width: 768px) {
  .attendance-dashboard {
    padding: 0.75rem;
  }
  
  .cards-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .timer-card {
    grid-column: 1;
  }
  
  .svg-container {
    max-width: 220px;
    margin: 0 auto 1rem;
  }
  
  .right-column {
    gap: 1rem;
  }
  
  .card {
    padding: 1rem;
  }
  
  .clock-btn {
    max-width: 120px;
    padding: 10px 20px;
  }
}

/* Small Mobile Styles */
@media (max-width: 480px) {
  .attendance-dashboard {
    padding: 0.5rem;
  }
  
  .cards-grid {
    gap: 0.75rem;
  }
  
  .card {
    padding: 0.75rem;
    border-radius: 8px;
  }
  
  .svg-container {
    max-width: 200px;
  }
  
  .timer-content {
    width: 85%;
  }
  
  .clock-btn {
    max-width: 110px;
    padding: 8px 16px;
    font-size: 0.8rem;
  }
  
  .activity-table {
    font-size: 0.7rem;
  }
  
  .activity-table th,
  .activity-table td {
    padding: 0.4rem 0.3rem;
  }
}

/* Extra Small Mobile Styles */
@media (max-width: 360px) {
  .attendance-dashboard {
    padding: 0.25rem;
  }
  
  .card {
    padding: 0.6rem;
    border-radius: 6px;
  }
  
  .svg-container {
    max-width: 180px;
  }
  
  .timer-display {
    font-size: 0.8rem;
  }
  
  .clock-btn {
    max-width: 100px;
    padding: 6px 12px;
    font-size: 0.75rem;
  }
  
  .info-item label {
    font-size: 0.7rem;
  }
  
  .info-item span {
    font-size: 0.75rem;
  }
}

/* Landscape Mobile Optimization */
@media (max-height: 500px) and (orientation: landscape) {
  .attendance-dashboard {
    padding: 0.5rem;
  }
  
  .cards-grid {
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }
  
  .svg-container {
    max-width: 160px;
  }
  
  .timer-display {
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
  }
  
  .clock-btn {
    max-width: 100px;
    padding: 6px 12px;
    font-size: 0.75rem;
  }
}

/* High DPI Screens */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
  .card {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }
}

/* Reduced motion for accessibility */
@media (prefers-reduced-motion: reduce) {
  .progress-ring-circle {
    transition: none;
  }
  
  .clock-btn {
    transition: opacity 0.3s;
  }
  
  .clock-btn:hover:not(:disabled) {
    transform: none;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .attendance-dashboard {
    background-color: #1a1a1a;
  }
  
  .card {
    background: #2d2d2d;
    color: #ffffff;
  }
  
  .card h2 {
    color: #ffffff;
  }
  
  .info-item span {
    color: #e0e0e0;
  }
  
  .location {
    color: #e0e0e0;
  }
  
  .activity-table th {
    background-color: #3d3d3d;
    color: #ffffff;
  }
  
  .activity-table tbody tr:hover {
    background-color: #3d3d3d;
  }
}
</style>