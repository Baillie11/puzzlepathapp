# Puzzle Path Quest - Standalone Web Application

A complete standalone web application for running interactive treasure hunts and puzzle quests. This app works independently of WordPress and can be deployed on any web server.

## Features

### Core Functionality
- **Booking Verification**: Secure booking number verification system
- **Interactive Quest Experience**: Step-by-step clue progression with hints and tasks
- **Timer & Progress Tracking**: Real-time timer with toggle visibility and progress bar
- **Mobile Responsive**: Optimized for all device sizes
- **Personalized Experience**: Dynamic greetings using customer names from bookings

### User Experience Enhancements
- **Timer Toggle**: Users can hide/show timer and progress elements
- **Personalized Greetings**: Random friendly greetings using customer's first name
- **Progress Visualization**: Visual progress bar showing quest completion
- **Professional UI**: Modern, branded interface with Puzzle Path styling

### Technical Features
- **Test Endpoints**: Working test environment with sample data
- **Quest Tracking**: Event tracking for analytics and completion monitoring
- **Session Management**: Secure data handling and completion data transfer
- **Single Page Application**: Smooth user experience without page reloads

## Directory Structure

```
unified-puzzle-path/
├── index.html              # Main quest application
├── register.html           # Completion registration page
├── test_json.php          # Booking verification endpoint (test)
├── get_clues.php          # Clue loading endpoint (test)
├── track_quest.php        # Quest event tracking
├── config.php             # Database configuration
├── database_schema.sql    # Database structure
├── populate_clues.sql     # Sample clue data
└── deploy/               # Production deployment files
```

## Quick Start

1. **Setup Web Server**: Ensure PHP and MySQL are available
2. **Configure Database**: Import `database_schema.sql` and `populate_clues.sql`
3. **Update Config**: Edit `config.php` with your database credentials
4. **Test**: Access `index.html` and use booking number "TEST123"

## Current Status

✅ **Working Features**:
- Booking verification with test data
- Complete quest flow from start to finish
- Timer toggle functionality
- Personalized greetings
- Mobile responsive design
- Progress tracking
- Quest completion flow

🔄 **Development Features**:
- Test endpoints provide sample data for development
- Easy integration points for production database
- Comprehensive tracking and analytics ready

## Integration

This standalone app can be integrated with:
- The Puzzle Path WordPress booking plugin
- External booking systems via REST API
- Custom databases and user management systems

## Deployment

See `DEPLOYMENT_GUIDE.md` for detailed production deployment instructions.

---

**Version**: 1.0.0
**Last Updated**: August 2024
**Status**: Complete with Enhanced UX Features
