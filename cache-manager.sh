#!/bin/bash

# Cache Manager Shell Script
# Provides easy command-line access to cache management

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_SCRIPT="$SCRIPT_DIR/cache-manager.php"
SIMPLE_TEST="$SCRIPT_DIR/simple-cache-test.php"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if PHP is available
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed or not in PATH"
    exit 1
fi

# Show usage
show_usage() {
    echo "Cache Manager - Server-Side Cache Tool"
    echo ""
    echo "Usage: $0 [command]"
    echo ""
    echo "Commands:"
    echo "  test       - Quick cache availability test"
    echo "  check      - Detailed cache detection"
    echo "  clear      - Clear all available caches"
    echo "  help       - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 test    # Quick test"
    echo "  $0 clear   # Clear all caches"
    echo ""
}

# Main script logic
case "${1:-help}" in
    "test")
        print_status "Running quick cache test..."
        php "$SIMPLE_TEST"
        ;;
    
    "check")
        print_status "Running detailed cache detection..."
        php "$PHP_SCRIPT"
        ;;
    
    "clear")
        print_warning "This will clear ALL available caches!"
        read -p "Are you sure? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            print_status "Clearing caches..."
            php "$PHP_SCRIPT" --clear
        else
            print_status "Cache clearing cancelled."
        fi
        ;;
    
    "help"|*)
        show_usage
        ;;
esac
