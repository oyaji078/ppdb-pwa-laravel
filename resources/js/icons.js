/**
 * Explicit Lucide icon set.
 *
 * Importing the whole library pulls in every icon (~600 kB). Listing the ones
 * the templates actually use lets the bundler drop the rest. Icon names that
 * administrators can type freely (facility and program icons) are included
 * generously; anything unknown falls back to a neutral shape rather than
 * rendering nothing.
 */
import {
    Archive, ArrowLeft, ArrowRight, AtSign, Award, Bell, BellOff, BookOpen, BookOpenText,
    Building2, Calendar, CalendarCheck, CalendarClock, CalendarDays, CalendarOff, Check,
    ChartColumn, ChartLine, ChartPie, ChevronDown, CircleAlert, CircleCheck, CircleDashed,
    CircleUser, ClipboardCheck, ClipboardList, Clock, ClockAlert, Download, ExternalLink,
    Eye, EyeOff, FileCheck, FileClock, FileDown, FilePlus, FileQuestionMark, FileSpreadsheet,
    FileStack, FileText, FileX, Filter, FlaskConical, FolderOpen, Gauge, Globe, GraduationCap,
    HeartPulse, History, House, Image, ImageOff, Images, Inbox, Info, KeyRound, Landmark,
    Languages, Layers, LayoutDashboard, Library, ListOrdered, Lock, LogIn, LogOut, Mail,
    MapPin, Megaphone, Menu, MessageCircle, Monitor, MoonStar, Newspaper, Palette, Pencil,
    Phone, PhoneOff, Plus, RefreshCw, RotateCcw, Route, Save, School, Search, Send,
    ServerCrash, Settings, Sheet, ShieldX, SlidersHorizontal, Trash2, TriangleAlert, Trophy,
    Undo2, Upload, User, UserCog, UserPlus, UserRound, UserX, Users, UsersRound, Volleyball,
    WifiOff, Wrench, X,
} from 'lucide';

export const icons = {
    Archive, ArrowLeft, ArrowRight, AtSign, Award, Bell, BellOff, BookOpen, BookOpenText,
    Building2, Calendar, CalendarCheck, CalendarClock, CalendarDays, CalendarOff, Check,
    ChartColumn, ChartLine, ChartPie, ChevronDown, CircleAlert, CircleCheck, CircleDashed,
    CircleUser, ClipboardCheck, ClipboardList, Clock, ClockAlert, Download, ExternalLink,
    Eye, EyeOff, FileCheck, FileClock, FileDown, FilePlus, FileQuestionMark, FileSpreadsheet,
    FileStack, FileText, FileX, Filter, FlaskConical, FolderOpen, Gauge, Globe, GraduationCap,
    HeartPulse, History, House, Image, ImageOff, Images, Inbox, Info, KeyRound, Landmark,
    Languages, Layers, LayoutDashboard, Library, ListOrdered, Lock, LogIn, LogOut, Mail,
    MapPin, Megaphone, Menu, MessageCircle, Monitor, MoonStar, Newspaper, Palette, Pencil,
    Phone, PhoneOff, Plus, RefreshCw, RotateCcw, Route, Save, School, Search, Send,
    ServerCrash, Settings, Sheet, ShieldX, SlidersHorizontal, Trash2, TriangleAlert, Trophy,
    Undo2, Upload, User, UserCog, UserPlus, UserRound, UserX, Users, UsersRound, Volleyball,
    WifiOff, Wrench, X,
};

/** Drawn when a template asks for an icon that is not in the set above. */
export const fallbackIcon = Info;

const toPascalCase = (name) =>
    name.split('-').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join('');

/**
 * Swaps unknown `data-lucide` names for the fallback so a mistyped icon in the
 * CMS never leaves an empty gap in the layout.
 */
export const normaliseIconNames = (root = document) => {
    root.querySelectorAll('[data-lucide]').forEach((element) => {
        const name = element.getAttribute('data-lucide');

        if (name && !(toPascalCase(name) in icons)) {
            element.setAttribute('data-lucide', 'info');
        }
    });
};
