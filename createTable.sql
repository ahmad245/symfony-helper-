CREATE TABLE `focale_territoire_1`.`mission_interesse`(  
  `id_mission` INT(11) NOT NULL,
  `id_stagiaire` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id_mission`, `id_stagiaire`),
  FOREIGN KEY (`id_mission`) REFERENCES `focale_territoire_1`.`mission`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
  FOREIGN KEY (`id_stagiaire`) REFERENCES `focale_territoire_1`.`stagiaire`(`id`) ON UPDATE RESTRICT ON DELETE CASCADE
);
