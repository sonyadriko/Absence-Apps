# API Documentation: Attendance Check-in/Check-out

## Endpoint: POST `/api/employee/attendance/checkin`

### Description
Process employee check-in or check-out with GPS location tracking.

### Authentication
Required. Use Bearer token in Authorization header.

### Request Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}
```

### Request Body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| event_type | string | Yes | Type of event: 'check_in' or 'check_out' |
| latitude | number | Yes | GPS latitude coordinate (range: -90 to 90) |
| longitude | number | Yes | GPS longitude coordinate (range: -180 to 180) |
| notes | string | No | Optional notes (max 500 characters) |

### Example Request
```json
{
    "event_type": "check_in",
    "latitude": -6.2088,
    "longitude": 106.8456,
    "notes": "Check in from mobile"
}
```

### Success Response (200 OK)
```json
{
    "success": true,
    "message": "Successfully checked in!",
    "data": {
        "event": {
            "event_type": "check_in",
            "event_time": "2025-08-28T05:29:25.274557Z",
            "is_late": false,
            "is_early_departure": false
        },
        "current_status": "checked_in",
        "message": "Successfully checked in!"
    }
}
```

### Error Response (422 Unprocessable Entity)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "event_type": ["The event type field is required."],
        "latitude": ["The latitude field is required."],
        "longitude": ["The longitude field is required."]
    }
}
```

### Error Response (403 Forbidden)
```json
{
    "success": false,
    "message": "Employee profile not found"
}
```

### Notes
- The system will automatically record the current timestamp
- GPS coordinates are used to validate location against branch geofence
- Check-out will calculate total work hours if check-in exists
- The system prevents duplicate check-ins on the same day

### Testing with cURL
```bash
# Check-in
curl -X POST http://localhost/absence-app/public/api/employee/attendance/checkin \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "event_type": "check_in",
    "latitude": -6.2088,
    "longitude": 106.8456,
    "notes": "Check in from office"
  }'

# Check-out
curl -X POST http://localhost/absence-app/public/api/employee/attendance/checkin \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "event_type": "check_out",
    "latitude": -6.2088,
    "longitude": 106.8456,
    "notes": "Leaving office"
  }'
```
