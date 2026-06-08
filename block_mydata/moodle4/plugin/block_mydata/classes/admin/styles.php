<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Shared CSS for the styled admin settings cards.
 *
 * Emitted once per setting block (duplicate <style> tags are harmless). Kept in
 * one place so the cards grid and the generic panels stay visually identical.
 *
 * @package    block_mydata
 * @copyright  2024 eh! ideas Tecnología Educativa
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mydata\admin;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the scoped stylesheet for the admin card UI.
 */
class styles {

    /**
     * Return the scoped CSS (raw, not sanitised).
     *
     * @return string
     */
    public static function css() {
        return '<style>
        .bm-admin-grid{display:flex;flex-wrap:wrap;gap:14px;margin:8px 0 6px;}
        .bm-admin-grid .bm-admin-card{flex:1 1 270px;}
        .bm-admin-card{border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:15px 16px;box-shadow:0 1px 3px rgba(20,42,77,.05);transition:box-shadow .15s,border-color .15s;}
        .bm-admin-card:hover{box-shadow:0 4px 14px rgba(20,42,77,.09);border-color:#cfd9e6;}
        .bm-admin-card-head{display:flex;align-items:flex-start;gap:12px;}
        .bm-admin-ic{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
        .bm-admin-titles{flex:1;min-width:0;}
        .bm-admin-zone{font-size:9.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#a3b0c2;margin-bottom:1px;}
        .bm-admin-card-head h4{font-size:14px;font-weight:700;margin:0;color:#1e293b;line-height:1.25;}
        .bm-admin-card-head p{font-size:11.5px;color:#94a3b8;margin:3px 0 0;line-height:1.35;}
        .bm-switch{position:relative;display:inline-block;width:42px;height:24px;flex-shrink:0;cursor:pointer;margin:0;}
        .bm-switch input{opacity:0;width:0;height:0;position:absolute;}
        .bm-switch span{position:absolute;inset:0;background:#cbd5e1;border-radius:999px;transition:.2s;}
        .bm-switch span:before{content:"";position:absolute;width:18px;height:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 2px rgba(0,0,0,.2);}
        .bm-switch input:checked+span{background:#2563eb;}
        .bm-switch input:checked+span:before{transform:translateX(18px);}
        .bm-admin-card-body{margin-top:13px;padding-top:13px;border-top:1px dashed #e8edf3;display:flex;flex-wrap:wrap;gap:14px;}
        .bm-admin-field{display:flex;flex-direction:column;gap:5px;}
        .bm-admin-field-full{width:100%;}
        .bm-admin-field label{font-size:11.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.03em;}
        .bm-color-wrap{display:flex;align-items:center;gap:8px;}
        input.bm-color{width:44px;height:30px;border:1px solid #cbd5e1;border-radius:7px;padding:2px;background:#fff;cursor:pointer;}
        .bm-color-wrap code{font-size:12px;color:#64748b;}
        input.bm-text{width:100%;padding:7px 10px;border:1px solid #cbd5e1;border-radius:7px;font-size:13px;}
        input.bm-num{width:96px;padding:7px 10px;border:1px solid #cbd5e1;border-radius:7px;font-size:13px;}
        .bm-num-wrap{display:flex;align-items:center;gap:7px;}
        .bm-num-wrap .bm-suffix{font-size:12px;color:#94a3b8;}
        .bm-admin-field-full small{font-size:11px;color:#94a3b8;line-height:1.3;}
        .bm-admin-warn{width:100%;display:flex;align-items:flex-start;gap:8px;background:#fff8eb;border:1px solid #fde7b8;border-radius:8px;padding:8px 11px;font-size:11.5px;line-height:1.4;color:#92670c;}
        .bm-admin-warn i{margin-top:1px;color:#d9890a;flex-shrink:0;}
        </style>';
    }
}
